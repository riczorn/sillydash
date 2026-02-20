<?php

namespace App\Models;

use App\Core\Model;

class Setup extends Model
{
    protected $table = ''; // No specific table

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
     */
    public function writeConfigArray(array $config): bool
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        try {
            return file_put_contents(ROOTPATH . 'config.php', $content) !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function testConnection(array $data)
    {
        $host = $data['db_host'] ?? 'localhost';
        $user = $data['db_user'] ?? '';
        $pass = $data['db_pass'] ?? '';
        $db = $data['db_name'] ?? '';
        $port = 3306;

        try {
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
