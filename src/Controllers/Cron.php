<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\IngestionService;

class Cron extends Controller
{
    public function index()
    {
        // Security: Allow CLI or require a valid cron token
        $isCli = (php_sapi_name() === 'cli');

        if (!$isCli) {
            $token = $this->getQuery('token');
            $config = require ROOTPATH . 'config.php';
            $expectedToken = $config['cron_token'] ?? 'change-me-in-config';

            if ($token !== $expectedToken) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }
        }

        $service = new IngestionService();

        // 1. Scan for new files
        $newFiles = $service->scanFiles();

        // 2. Process queue
        $days = max(1, min(730, (int) ($this->getQuery('days') ?? 1)));
        $limit = $days * 5;

        $processed = $service->processQueue($limit);

        // Fetch stats
        $fileModel = new \App\Models\FileModel();
        $stats = $fileModel->getStats();

        return $this->json([
            'status' => 'success',
            'message' => 'Cron job executed',
            'new_files_scanned' => $newFiles,
            'files_processed' => $processed,
            'stats' => [
                'total_files' => (int) ($stats['total'] ?? 0),
                'processed_files' => (int) ($stats['processed'] ?? 0),
                'pending_files' => (int) ($stats['pending'] ?? 0)
            ]
        ]);
    }
}
