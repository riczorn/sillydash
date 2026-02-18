<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\UserModel;

class CreateAdmin extends BaseCommand
{
    protected $group = 'Custom';
    protected $name = 'user:create_admin';
    protected $description = 'Creates the admin user';

    public function run(array $params)
    {
        $userModel = new UserModel();

        $data = [
            'username' => 'admin',
            'password' => 'silly',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ];

        // Check if admin exists
        $existing = $userModel->where('username', 'admin')->first();

        if ($existing) {
            CLI::write("Admin user already exists. Updating password...", 'yellow');
            $userModel->update($existing['id'], ['password' => 'silly']);
            CLI::write("Password updated.", 'green');
        } else {
            CLI::write("Creating admin user...", 'green');
            if (!$userModel->insert($data)) {
                CLI::error("Failed to create user:");
                foreach ($userModel->errors() as $error) {
                    CLI::error($error);
                }
            } else {
                CLI::write("Admin user created successfully.", 'green');
            }
        }
    }
}
