<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\Config;

class VerifyInternal extends BaseCommand
{
    protected $group = 'Silly';
    protected $name = 'verify:internal';
    protected $description = 'Internal check for Config model and DB connection.';

    public function run(array $params)
    {
        CLI::write('Internal Verification Starting...', 'yellow');

        $model = new Config();

        CLI::write('Config Model Loaded status: ' . ($model->loaded ? 'TRUE' : 'FALSE'), $model->loaded ? 'green' : 'red');
        CLI::write('DB Prefix: ' . $model->db_prefix);

        // Verify properties were read
        if ($model->db_prefix === 'silly_test_prefix_' && $model->list_folder === './test_files_mysql') {
            CLI::write('SUCCESS: Config model successfully read config.php properties.', 'green');
        } else {
            CLI::write('FAILURE: Config model did not read properties correctly.', 'red');
            return;
        }

        // Verify loaded status (should be false because creds are invalid, but we want to check the error message)
        // Actually, if we want to verify it ATTEMPTED to connect using the right user, we need to try connecting.

        try {
            $db = \Config\Database::connect();
            $db->initialize();
        } catch (\Throwable $e) {
            CLI::write('Caught DB Exception: ' . $e->getMessage());
            if (strpos($e->getMessage(), 'silly_test_user') !== false) {
                CLI::write('SUCCESS: Database attempted connection with test user.', 'green');
            } else {
                CLI::write('FAILURE: Database error did not contain test user. Got: ' . $e->getMessage(), 'red');
            }
        }
    }
}
