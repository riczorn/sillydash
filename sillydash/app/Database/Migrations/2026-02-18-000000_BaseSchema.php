<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BaseSchema extends Migration
{
    public function up()
    {
        // Re-initialize forge with the injected DB connection to avoid using default group
        $this->forge = \Config\Database::forge($this->db);

        // Users Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'user'],
                'default' => 'user',
            ],
            'allowed_accounts' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users', true);

        // Accounts Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'parent_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'default' => null,
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'home_directory' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'db_names' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('domain');
        $this->forge->addKey('username');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('accounts', true);

        // Files Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'filename' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'unique' => true,
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'sizes',
            ],
            'file_date' => [
                'type' => 'DATETIME',
            ],
            'processed' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('files', true);

        // Records Table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'file_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'account_id' => [
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
                'null' => true,
            ],
            'size_bytes' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'default' => 0,
            ],
            'kind' => [
                'type' => 'VARCHAR',
                'constraint' => 50, // e.g., 'account', 'db', 'mail', 'spam', 'log'
                'default' => 'disk',
            ],
            'path' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
            ],
            'time' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('file_id');
        $this->forge->addKey('account_id');
        $this->forge->addForeignKey('file_id', 'files', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('account_id', 'accounts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('records', true);
    }

    public function down()
    {
        $this->forge->dropTable('records', true);
        $this->forge->dropTable('accounts', true);
        $this->forge->dropTable('files', true);
        $this->forge->dropTable('users', true);
    }
}
