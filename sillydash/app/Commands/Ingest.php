<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\IngestionService;

class Ingest extends BaseCommand
{
    protected $group = 'Custom';
    protected $name = 'ingest:run';
    protected $description = 'Runs the file ingestion process';

    public function run(array $params)
    {
        CLI::write('Starting Ingestion...', 'green');

        $service = new IngestionService();

        // Scan files
        CLI::write('Scanning files...');
        $newFilesCount = $service->scanFiles();
        CLI::write("New files found: $newFilesCount");

        $days = $params['days'] ?? CLI::getOption('days') ?? 1;
        $limit = max(1, (int) $days) * 5;

        // Process queue
        CLI::write("Processing queue (Days: $days, Limit: $limit)...");
        $processed = $service->processQueue($limit);
        CLI::write("Files processed: $processed");

        // Report Accounts Count
        $db = \Config\Database::connect();
        $count = $db->table('accounts')->countAllResults();
        CLI::write("Total Accounts in DB: $count", 'yellow');

        // Report Records Count
        $countRecords = $db->table('records')->countAllResults();
        CLI::write("Total Records in DB: $countRecords", 'yellow');

        CLI::write('Done!', 'green');
    }
}
