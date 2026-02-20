<?php

namespace App\Services;

use App\Models\FileModel;
use App\Models\AccountModel;
use App\Models\RecordModel;
use App\Libraries\FileParser;
use App\Core\Database;

class IngestionService
{
    protected $fileModel;
    protected $accountModel;
    protected $recordModel;
    protected $parser;
    protected $accountCache = [];

    public function __construct()
    {
        $this->fileModel = new FileModel();
        $this->accountModel = new AccountModel();
        $this->recordModel = new RecordModel();
        $this->parser = new FileParser();
    }

    /**
     * Resolves the list folder path, handling relative paths.
     */
    private function getListFolder(): string
    {
        $config = require ROOTPATH . 'config.php';
        $folder = $config['list_folder'] ?? './files';

        if (strpos($folder, './') === 0 || strpos($folder, '../') === 0) {
            $folder = ROOTPATH . $folder;
        }
        return rtrim($folder, '/') . '/';
    }

    /**
     * Scans the list folder for new files and correctly adds them to the DB.
     * 
     * @return int Number of new files found
     */
    public function scanFiles()
    {
        $folder = $this->getListFolder();

        if (!is_dir($folder))
            return 0;

        $files = glob($folder . '*.list');
        $count = 0;

        foreach ($files as $filepath) {
            $filename = basename($filepath);

            // Check if exists
            // Model::where returns array of results
            $exists = $this->fileModel->where('filename', $filename);
            if (!empty($exists)) {
                continue;
            }

            // Parse metadata
            $meta = $this->parser->parseFilename($filename);
            if (!$meta)
                continue;

            $this->fileModel->insert([
                'filename' => $filename,
                'type' => $meta['type'],
                'file_date' => $meta['generated_at'], // Mapped to file_date
                'processed' => 0, // Default to pending
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Processes the ingestion queue.
     * Prioritizes Virtualmin account files.
     * 
     * @param int $limit Max number of data files to process
     */
    public function processQueue($limit = 5)
    {
        $processedCount = 0;
        $folder = $this->getListFolder();
        $db = Database::getInstance();

        // 1. Always process LATEST pending Virtualmin file first
        $prefix = $db->getPrefix();
        $filesTable = $prefix . 'files';

        $sql = "SELECT * FROM {$filesTable} WHERE processed = 0 AND type = 'virtualmin' ORDER BY file_date DESC LIMIT 1";
        $latestVirtualmin = $db->fetch($sql);

        // If we found one, mark all OLDER pending virtualmin files as processed (skipped)
        if ($latestVirtualmin) {
            $sql = "SELECT * FROM {$filesTable} WHERE processed = 0 AND type = 'virtualmin' AND id != ?";
            $olderfiles = $db->fetchAll($sql, [$latestVirtualmin['id']]);

            foreach ($olderfiles as $old) {
                $this->fileModel->update($old['id'], ['processed' => 1]);
            }

            // Process the latest one
            try {
                $content = @file_get_contents($folder . $latestVirtualmin['filename']);
                if ($content === false) {
                    throw new \Exception("Could not read file");
                }

                $entries = $this->parser->parseContent($content, 'virtualmin');

                // First pass: Insert/Update accounts without parent_id
                foreach ($entries as $entry) {
                    $existing = $this->accountModel->where('domain', $entry['domain']);
                    $existing = $existing[0] ?? null; // Get first result

                    $data = [
                        'domain' => $entry['domain'],
                        'username' => $entry['username'],
                        'home_directory' => $entry['home_directory'],
                        'db_names' => $entry['db_names'] ?? '',
                    ];

                    if ($existing) {
                        $this->accountModel->update($existing['id'], $data);
                    } else {
                        $this->accountModel->insert($data);
                    }
                }

                // Second pass: Update parent_id based on Path structure
                $allAccounts = $this->accountModel->findAll();

                foreach ($allAccounts as $child) {
                    if (empty($child['home_directory']))
                        continue;

                    if (strpos($child['home_directory'], '/domains/') !== false) {
                        // It's a sub-server
                        $parts = explode('/domains/', $child['home_directory']);
                        $parentPath = $parts[0];

                        // Find parent
                        $parent = $this->accountModel->where('home_directory', $parentPath);
                        $parent = $parent[0] ?? null;

                        if ($parent) {
                            $this->accountModel->update($child['id'], ['parent_id' => $parent['id']]);
                        }
                    }
                }

                $this->fileModel->update($latestVirtualmin['id'], ['processed' => 1]);
            } catch (\Exception $e) {
                $this->fileModel->update($latestVirtualmin['id'], ['processed' => -1]);
                // error_log('Virtualmin processing error: ' . $e->getMessage());
            }
        }

        // 2. Process Data Files
        $pendingFiles = $this->fileModel->getUnprocessed($limit);

        foreach ($pendingFiles as $file) {
            if ($file['type'] === 'virtualmin')
                continue;

            try {
                $content = @file_get_contents($folder . $file['filename']);
                if ($content === false) {
                    throw new \Exception("Could not read file");
                }

                $records = $this->parser->parseContent($content, $file['type']);
                unset($content);

                $kind = 'account';
                if (in_array($file['type'], ['mail', 'db', 'log', 'spam'])) {
                    $kind = $file['type'];
                }

                if ($file['type'] === 'sizes') {
                    $records = $this->computeAccountSizesFromChildren($records);
                }

                $batch = [];
                $batchSize = 500;

                $db->beginTransaction();

                foreach ($records as $row) {
                    $accountId = null;
                    $dbName = null;

                    if ($file['type'] === 'db') {
                        $dbName = basename($row['path']);
                    }

                    $cacheKey = $row['path'] . '|' . ($dbName ?? '');
                    if (isset($this->accountCache[$cacheKey])) {
                        $accountId = $this->accountCache[$cacheKey];
                    } else {
                        $account = $this->accountModel->findDomainByPathOrDb($row['path'], $dbName);
                        $accountId = $account ? $account['id'] : null;
                        $this->accountCache[$cacheKey] = $accountId;
                    }

                    $sizeBytes = $this->parser->parseSizeBytes($row['size_raw']);

                    if ($sizeBytes < 1048576) {
                        continue;
                    }

                    $batch[] = [
                        'file_id' => $file['id'],
                        'account_id' => $accountId,
                        'size_bytes' => $sizeBytes,
                        'kind' => $kind,
                        'path' => $row['path'],
                        'time' => $file['file_date']
                    ];

                    if (count($batch) >= $batchSize) {
                        $this->recordModel->insertBatch($batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $this->recordModel->insertBatch($batch);
                }

                $db->commit();
                unset($records, $batch);

                $this->fileModel->update($file['id'], ['processed' => 1]);
                $processedCount++;

                if ($processedCount % 50 === 0) {
                    gc_collect_cycles();
                }
            } catch (\Exception $e) {
                if ($db->getConnection()->inTransaction()) {
                    $db->rollBack();
                }
                $this->fileModel->update($file['id'], ['processed' => -1]);
                // error_log('File processing error: ' . $e->getMessage());
            }
        }

        return $processedCount;
    }

    protected function computeAccountSizesFromChildren(array $records): array
    {
        return array_values(array_filter($records, function ($row) {
            return rtrim($row['path'], '/') !== '/home';
        }));
    }
}
