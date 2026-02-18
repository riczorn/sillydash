<?php

namespace App\Models;

use CodeIgniter\Model;

class Setup extends Model
{
    protected $table = ''; // No specific table for this model

    /**
     * Generate config.php content using var_export for safety.
     */
    public function generateConfig(array $data): string
    {
        $config = [
            'database' => [
                'hostname' => $data['db_host'] ?? 'localhost',
                'username' => $data['db_user'] ?? '',
                'password' => $data['db_pass'] ?? '',
                'database' => $data['db_name'] ?? '',
                'DBDriver' => 'MySQLi',
                'DBPrefix' => $data['db_prefix'] ?: 'silly_',
                'port' => 3306,
            ],
            // 'db_prefix' removed in favor of database.DBPrefix
            'list_folder' => $data['list_folder'] ?? './files',
            'cron_token' => $data['cron_token'] ?? 'super-secret-token',
        ];

        // Include SMTP settings if provided
        if (!empty($data['smtp_host'])) {
            $config['smtp'] = [
                'host' => $data['smtp_host'] ?? '',
                'port' => (int) ($data['smtp_port'] ?? 587),
                'security' => $data['smtp_security'] ?? 'tls',
                'user' => $data['smtp_user'] ?? '',
                'pass' => $data['smtp_pass'] ?? '',
            ];
        }

        return "<?php\n\nreturn " . var_export($config, true) . ";\n";
    }

    /**
     * Save config array to config.php.
     */
    public function saveConfig(array $data): bool
    {
        $configContent = $this->generateConfig($data);
        try {
            return file_put_contents(ROOTPATH . 'config.php', $configContent) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Write an already-built config array directly to config.php.
     * Used by the Settings controller to merge and save.
     */
    public static function writeConfigArray(array $config): bool
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        try {
            return file_put_contents(ROOTPATH . 'config.php', $content) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Builds a DB connection config array from user-provided data.
     *
     * @param array $data Setup form data
     * @return array CI4-compatible connection config
     */
    public function buildConnectionConfig(array $data): array
    {
        return [
            'DSN' => '',
            'hostname' => $data['db_host'],
            'username' => $data['db_user'],
            'password' => $data['db_pass'] ?? '',
            'database' => $data['db_name'],
            'DBDriver' => 'MySQLi',
            'DBPrefix' => $data['db_prefix'] ?: 'silly_',
            'pConnect' => false,
            'DBDebug' => false, // Disable exception throwing for connection tests
            'charset' => 'utf8',
            'DBCollat' => 'utf8_general_ci',
            'swapPre' => '',
            'encrypt' => false,
            'compress' => false,
            'strictOn' => false,
            'failover' => [],
            'port' => 3306,
        ];
    }

    public function testConnection(array $data)
    {
        // Use raw MySQLi to avoid CI4 exception handling mechanism that might trigger 401 popups
        $host = $data['db_host'] ?? 'localhost';
        $user = $data['db_user'] ?? '';
        $pass = $data['db_pass'] ?? '';
        $db = $data['db_name'] ?? '';
        $port = 3306;

        try {
            // Suppress warnings with @ to prevent PHP errors from leaking to output
            $mysqli = @new \mysqli($host, $user, $pass, $db, $port);

            if ($mysqli->connect_error) {
                return false;
            }

            $mysqli->close();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
