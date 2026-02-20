<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setup as SetupModel;
use App\Models\UserModel;
use App\Core\Database;

class Setup extends Controller
{
    public function index()
    {
        // Security: If config already exists and is loaded, redirect to home
        if (file_exists(ROOTPATH . 'config.php')) {
            // Check if we can connect? 
            // If config exists, we assume setup is done or invalid.
            // But if invalid, we might want to re-run setup?
            // Usually, we check if installed.
            // Let's rely on config existence + DB connection check in Home.
            // But if user accesses /setup directly, we should check.
            try {
                // Try to load config and connect
                $db = Database::getInstance()->getConnection();
                // If successful, redirect to home
                $this->redirect(site_url('/'));
            } catch (\Exception $e) {
                // If connection fails but config exists, maybe allow setup or show error?
                // For now, let's allow setup page if connection fails?
                // Or just show page.
            }
        }

        $this->view('setup/index');
    }

    public function attemptSetup()
    {
        $data = $this->getPost();

        // Simple validation
        $required = ['db_host', 'db_name', 'db_user', 'admin_user', 'admin_email', 'admin_pass'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                // Flash error and redirect back? 
                // Creating a simple "flash" mechanism via session
                $this->setSession('error', "Field $field is required");
                $this->redirect(site_url('setup'));
            }
        }

        // Sanitize text inputs
        foreach (['db_host', 'db_name', 'db_user', 'db_prefix', 'list_folder', 'cron_token', 'admin_user', 'admin_email'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = trim(strip_tags($data[$key]));
            }
        }

        $setupModel = new SetupModel();

        // 1. Test Connection
        if (!$setupModel->testConnection($data)) {
            $this->setSession('error', 'Could not connect to the database with provided credentials.');
            $this->redirect(site_url('setup'));
        }

        // 2. Save Config
        $manualConfigNeeded = false;
        $configContent = '';
        if (!$setupModel->saveConfig($data)) {
            $manualConfigNeeded = true;
            $configContent = $setupModel->generateConfig($data);
        }

        // 3. Initialize DB (Run SQL file)
        try {
            $this->runDatabaseSetup($data);
        } catch (\Throwable $e) {
            // error_log('Setup Error: ' . $e->getMessage());

            if ($manualConfigNeeded) {
                $this->view('setup/manual_config', [
                    'configContent' => $configContent,
                    'message' => 'Failed to write config.php AND failed to initialize database: ' . $e->getMessage() . '. Please verify database credentials and permissions.'
                ]);
                return;
            }

            $this->setSession('error', 'Configuration saved but specific setup failed: ' . $e->getMessage());
            $this->redirect(site_url('/'));
        }

        if ($manualConfigNeeded) {
            $this->view('setup/manual_config', [
                'configContent' => $configContent,
                'message' => 'Setup completed successfully, but we could not write the config.php file. Please create it manually in the root directory (parent of app) with the following content:'
            ]);
            return;
        }

        $this->setSession('message', 'Setup completed successfully!');
        $this->redirect(site_url('/'));
    }

    private function runDatabaseSetup($data)
    {
        // Connect to the database using the provided credentials
        $dsn = "mysql:host={$data['db_host']};dbname={$data['db_name']};charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        try {
            $pdo = new \PDO($dsn, $data['db_user'], $data['db_pass'] ?? '', $options);
        } catch (\PDOException $e) {
            // If database doesn't exist, try to create it?
            // Usually Setup expects DB to exist or user having CREATE privileges.
            // CodeIgniter's forge allows creating DB.
            // Let's assume DB exists as per `testConnection` check usually checks connection to DB name or server.
            // My testConnection in SetupModel connects to DB if provided.
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }

        // Read SQL file
        $sqlFile = ROOTPATH . 'src/Database/database.sql';
        if (!file_exists($sqlFile)) {
            throw new \Exception("Database schema file not found.");
        }

        $sql = file_get_contents($sqlFile);

        $prefix = $data['db_prefix'] ?? '';
        if (!empty($prefix)) {
            $sql = str_replace(
                ['`users`', '`accounts`', '`files`', '`records`'],
                ["`{$prefix}users`", "`{$prefix}accounts`", "`{$prefix}files`", "`{$prefix}records`"],
                $sql
            );
        }

        // Execute SQL statements
        // Split by semicolon, but handle cases where semicolon is inside strings?
        // Basic split is usually fine for simple schema.
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }

        // Create Admin User
        $usersTable = $prefix . 'users';
        // Check if admin exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$usersTable} WHERE username = ?");
        $stmt->execute([$data['admin_user']]);
        if ($stmt->fetchColumn() == 0) {
            $password = password_hash($data['admin_pass'], PASSWORD_DEFAULT);
            $now = date('Y-m-d H:i:s');

            $insert = $pdo->prepare("INSERT INTO {$usersTable} (username, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'admin', ?, ?)");
            $insert->execute([
                $data['admin_user'],
                $data['admin_email'],
                $password,
                $now,
                $now
            ]);
        }
    }
}
