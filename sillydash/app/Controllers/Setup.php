<?php

namespace App\Controllers;

use App\Models\Setup as SetupModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

class Setup extends BaseController
{
    public function index()
    {
        // Security: If config already exists and is loaded, redirect to home
        if (file_exists(ROOTPATH . 'config.php')) {
            $configModel = new \App\Models\Config();
            if ($configModel->loaded) {
                return redirect()->to('/');
            }
        }

        return view('setup/index');
    }

    public function attemptSetup()
    {
        $rules = [
            'db_host' => 'required',
            'db_name' => 'required',
            'db_user' => 'required',
            'admin_user' => 'required|min_length[3]',
            'admin_email' => 'required|valid_email',
            'admin_pass' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();

        // Sanitize text inputs
        foreach (['db_host', 'db_name', 'db_user', 'db_prefix', 'list_folder', 'cron_token', 'admin_user', 'admin_email'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = trim(strip_tags($data[$key]));
            }
        }

        $setupModel = new SetupModel();

        // 1. Test Connection
        if (!$setupModel->testConnection($data)) {
            return redirect()->back()->withInput()->with('error', 'Could not connect to the database with provided credentials.');
        }

        // 2. Save Config
        $manualConfigNeeded = false;
        $configContent = '';
        if (!$setupModel->saveConfig($data)) {
            $manualConfigNeeded = true;
            $configContent = $setupModel->generateConfig($data);
            // Don't return error yet, try to run migrations first
        }

        // 3. Initialize DB (Migrations)
        try {
            $this->runMigrationsAndSeed($data);
        } catch (\Throwable $e) {
            log_message('error', 'Setup Migration/Seed Error: ' . $e->getMessage());

            // If manual config was also needed, the user is in a tough spot.
            if ($manualConfigNeeded) {
                return view('setup/manual_config', [
                    'configContent' => $configContent,
                    'message' => 'Failed to write config.php AND failed to initialize database: ' . $e->getMessage() . '. Please verify database credentials and permissions.'
                ]);
            }

            return redirect()->to('/')->with('error', 'Configuration saved but basic setup failed: ' . $e->getMessage());
        }

        if ($manualConfigNeeded) {
            return view('setup/manual_config', [
                'configContent' => $configContent,
                'message' => 'Setup completed successfully, but we could not write the config.php file. Please create it manually in the root directory (parent of app) with the following content:'
            ]);
        }

        return redirect()->to('/')->with('message', 'Setup completed successfully!');
    }

    // Helper to run migration in the same request context using dynamic connection
    private function runMigrationsAndSeed($data)
    {
        // Use the same connection config builder as the Setup model
        $setupModel = new SetupModel();
        $customConfig = $setupModel->buildConnectionConfig($data);

        // Force a NEW connection instance with these credentials, avoiding global 'default' state
        $db = \Config\Database::connect($customConfig, false);

        // 2. Run Migrations
        // Pass the explicit DB connection to the migration runner
        // IMPORTANT: Pass 'false' as 3rd arg to force a NEW instance, otherwise it reuses the global one with default DB
        $migrate = \Config\Services::migrations(null, $db, false);

        try {
            $migrate->latest();
        } catch (\Throwable $e) {
            throw new \RuntimeException("Migration failed: " . $e->getMessage());
        }

        // 3. Create Admin
        // Use Query Builder directly on the connection to avoid Model connection logic
        $builder = $db->table('users');

        $adminData = [
            'username' => $data['admin_user'],
            'email' => $data['admin_email'],
            'password' => password_hash($data['admin_pass'], PASSWORD_DEFAULT), // Manual hash since we bypass Model
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Check if admin exists
        if ($builder->where('username', $adminData['username'])->countAllResults() === 0) {
            $builder->insert($adminData);
        }
    }
}
