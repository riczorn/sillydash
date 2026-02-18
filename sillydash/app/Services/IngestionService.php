<?php

namespace App\Services;

use App\Models\FileModel;
use App\Models\AccountModel;
use App\Models\RecordModel;
use App\Libraries\FileParser;
use App\Models\Config;

class IngestionService
{
    protected $fileModel;
    protected $accountModel;
    protected $recordModel;
    protected $parser;
    protected $configModel;
    protected $accountCache = [];

    public function __construct($fileModel = null, $accountModel = null, $recordModel = null, $configModel = null)
    {
        $this->fileModel = $fileModel ?? new FileModel();
        $this->accountModel = $accountModel ?? new AccountModel();
        $this->recordModel = $recordModel ?? new RecordModel();
        $this->parser = new FileParser();
        $this->configModel = $configModel ?? new Config();
    }

    /**
     * Resolves the list folder path, handling relative paths.
     */
    private function getListFolder(): string
    {
        $folder = $this->configModel->list_folder;
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
            if ($this->fileModel->where('filename', $filename)->first()) {
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

        // 1. Always process LATEST pending Virtualmin file first
        // We only need the latest one to have the current state of accounts
        $latestVirtualmin = $this->fileModel->where('processed', 0)
            ->where('type', 'virtualmin')
            ->orderBy('file_date', 'DESC')
            ->first();

        // If we found one, mark all OLDER pending virtualmin files as processed (skipped) to avoid redundant work
        if ($latestVirtualmin) {
            $olderfiles = $this->fileModel->where('processed', 0)
                ->where('type', 'virtualmin')
                ->where('id !=', $latestVirtualmin['id'])
                ->findAll();

            foreach ($olderfiles as $old) {
                $this->fileModel->update($old['id'], ['processed' => 1]); // Mark as processed (skipped)
            }

            // Process the latest one
            try {
                $content = file_get_contents($folder . $latestVirtualmin['filename']);
                if ($content === false) {
                    throw new \Exception("Could not read file");
                }

                $entries = $this->parser->parseContent($content, 'virtualmin');

                // First pass: Insert/Update accounts without parent_id
                // We need to ensure parents exist before linking
                foreach ($entries as $entry) {
                    $existing = $this->accountModel->where('domain', $entry['domain'])->first();
                    $data = [
                        'domain' => $entry['domain'],
                        'username' => $entry['username'],
                        'home_directory' => $entry['home_directory'],
                        'db_names' => $entry['db_names'] ?? '',
                        // 'parent_id' => null // Don't set yet
                    ];

                    if ($existing) {
                        $this->accountModel->update($existing['id'], $data);
                    } else {
                        $this->accountModel->insert($data);
                    }
                }

                // Second pass: Update parent_id based on Path structure
                // Logic: Sub-servers are in /home/user/domains/sub.domain
                // Parent is /home/user
                $allAccounts = $this->accountModel->findAll();

                foreach ($allAccounts as $child) {
                    if (empty($child['home_directory']))
                        continue;

                    if (strpos($child['home_directory'], '/domains/') !== false) {
                        // It's a sub-server
                        // Extract parent path: /home/user
                        $parts = explode('/domains/', $child['home_directory']);
                        $parentPath = $parts[0];

                        // Find parent
                        $parent = $this->accountModel->where('home_directory', $parentPath)->first();

                        if ($parent) {
                            $this->accountModel->update($child['id'], ['parent_id' => $parent['id']]);
                        }
                    }
                }

                $this->fileModel->update($latestVirtualmin['id'], ['processed' => 1]);
            } catch (\Exception $e) {
                $this->fileModel->update($latestVirtualmin['id'], ['processed' => -1]);
                log_message('error', 'Virtualmin processing error: ' . $e->getMessage());
            }
        }

        // 2. Process Data Files (limit applied here)
        $pendingFiles = $this->fileModel->getUnprocessed($limit);

        foreach ($pendingFiles as $file) {
            // Skip virtualmin files here as they are handled above
            if ($file['type'] === 'virtualmin')
                continue;

            try {
                $content = file_get_contents($folder . $file['filename']);
                if ($content === false) {
                    throw new \Exception("Could not read file");
                }

                $records = $this->parser->parseContent($content, $file['type']);
                unset($content); // Free raw file content immediately

                // Prepare kind
                $kind = 'account'; // Default
                if (in_array($file['type'], ['mail', 'db', 'log', 'spam'])) {
                    $kind = $file['type'];
                }

                // For 'sizes' files: compute main account sizes by summing children
                // instead of using the (rounded) total line from du output
                if ($file['type'] === 'sizes') {
                    $records = $this->computeAccountSizesFromChildren($records);
                }

                $batch = [];
                $batchSize = 500;

                // Wrap all inserts for this file in a transaction
                $db = \Config\Database::connect();
                $db->transStart();

                foreach ($records as $row) {
                    // Map to account_id with caching
                    $accountId = null;
                    $dbName = null;

                    if ($file['type'] === 'db') {
                        $dbName = basename($row['path']);
                    }

                    // Cache key for account lookup
                    $cacheKey = $row['path'] . '|' . ($dbName ?? '');
                    if (isset($this->accountCache[$cacheKey])) {
                        $accountId = $this->accountCache[$cacheKey];
                    } else {
                        $account = $this->accountModel->findDomainByPathOrDb($row['path'], $dbName);
                        $accountId = $account ? $account['id'] : null;
                        $this->accountCache[$cacheKey] = $accountId;
                        unset($account);
                    }

                    $sizeBytes = $this->parser->parseSizeBytes($row['size_raw']);

                    // Skip entries smaller than 1 MB
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

                $db->transComplete();
                unset($records, $batch); // Free parsed records and batch

                $this->fileModel->update($file['id'], ['processed' => 1]);
                $processedCount++;

                // Periodic garbage collection to prevent memory buildup
                if ($processedCount % 50 === 0) {
                    gc_collect_cycles();
                }
            } catch (\Exception $e) {
                $this->fileModel->update($file['id'], ['processed' => -1]);
                log_message('error', 'File processing error (' . $file['filename'] . '): ' . $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * For sizes files: skip the grand total line (/home/) since the chart
     * computes the total by summing individual account records.
     * Individual account sizes from du are kept as-is.
     *
     * @param array $records Parsed [size_raw, path] entries
     * @return array Records without the grand total line
     */
    protected function computeAccountSizesFromChildren(array $records): array
    {
        return array_values(array_filter($records, function ($row) {
            return rtrim($row['path'], '/') !== '/home';
        }));
    }
}
