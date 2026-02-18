<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorMappingsToAccounts extends Migration
{
    public function up()
    {
        // Use the injected DB connection
        $db = $this->db;

        // Re-initialize forge with the injected DB connection
        $this->forge = \Config\Database::forge($db);

        // 1. Rename table mappings -> accounts
        // Check if table exists first to avoid errors if run multiple times or manually changed
        if ($db->tableExists('mappings')) {
            $this->forge->renameTable('mappings', 'accounts');
        }

        // 2. Rename column mapping_id -> account_id in records
        // MySQL specific: CHANGE COLUMN old_name new_name definition
        // We need to know the definition content. 
        // Previously mapping_id was INT(9) UNSIGNED

        $fields = [
            'mapping_id' => [
                'name' => 'account_id',
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
                'null' => true,
            ],
        ];

        // Verify column exists before modification
        if ($db->fieldExists('mapping_id', 'records')) {
            $this->forge->modifyColumn('records', $fields);
        }
    }

    public function down()
    {
        // Revert rename table
        $this->forge->renameTable('accounts', 'mappings');

        // Revert column rename
        $fields = [
            'account_id' => [
                'name' => 'mapping_id',
                'type' => 'INT',
                'constraint' => 9,
                'unsigned' => true,
            ],
        ];
        $this->forge->modifyColumn('records', $fields);
    }
}
