<?php

namespace App\Controllers;

use App\Services\IngestionService;
use CodeIgniter\Controller;

class Cron extends BaseController
{
    public function index()
    {
        // Security: Allow CLI or require a valid cron token
        if (!is_cli()) {
            $token = $this->request->getGet('token');
            $config = require ROOTPATH . 'config.php';
            $expectedToken = $config['cron_token'] ?? 'change-me-in-config';
            if ($token !== $expectedToken) {
                return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(403);
            }
        }

        $service = new IngestionService();

        // 1. Scan for new files
        $newFiles = $service->scanFiles();

        // 2. Process queue
        $days = max(1, min(730, (int) ($this->request->getGet('days') ?? 1)));
        $limit = $days * 5;

        $processed = $service->processQueue($limit);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Cron job executed',
            'new_files_scanned' => $newFiles,
            'files_processed' => $processed
        ]);
    }
}
