<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifyWrapper extends BaseCommand
{
    protected $group = 'Silly';
    protected $name = 'verify:setup'; // Keeping the name requested in plan
    protected $description = 'Sets up config and runs internal verification in new process.';

    public function run(array $params)
    {
        CLI::write('Wrapper: Backing up and creating config...', 'yellow');

        $configFile = ROOTPATH . 'config.php';
        $backupFile = ROOTPATH . 'config.php.bak';
        $hasOriginal = false;

        if (file_exists($configFile)) {
            rename($configFile, $backupFile);
            $hasOriginal = true;
        }

        try {
            // Create Test Config
            $testUser = 'silly_test_user';
            $testConfig = [
                'database' => [
                    'hostname' => 'localhost',
                    'username' => $testUser,
                    'password' => 'some_pass',
                    'database' => 'silly_test_db',
                    'DBDriver' => 'MySQLi',
                    'DBPrefix' => 'silly_',
                    'pConnect' => false,
                    'DBDebug' => true,
                    'charset' => 'utf8',
                    'DBCollat' => 'utf8_general_ci',
                    'swapPre' => '',
                    'encrypt' => false,
                    'compress' => false,
                    'strictOn' => false,
                    'failover' => [],
                    'port' => 3306,
                ],
                'db_prefix' => 'silly_test_prefix_',
                'list_folder' => './test_files_mysql',
            ];

            $content = "<?php\n\nreturn " . var_export($testConfig, true) . ";";
            file_put_contents($configFile, $content);

            CLI::write('Wrapper: Running verify:internal in new process...', 'yellow');

            // Execute command
            passthru('php spark verify:internal');

        } catch (\Throwable $e) {
            CLI::error('Wrapper Exception: ' . $e->getMessage());
        } finally {
            // Restore Config
            if (file_exists($configFile)) {
                unlink($configFile);
            }
            if ($hasOriginal) {
                rename($backupFile, $configFile);
                CLI::write('Wrapper: Restored original config.php', 'yellow');
            }
        }
    }
}
