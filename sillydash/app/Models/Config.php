<?php

namespace App\Models;

use CodeIgniter\Model;

class Config extends Model
{
    public bool $loaded = false;
    public string $db_prefix = 'silly_';
    public string $list_folder = './files';
    public array $configData = [];

    public function __construct()
    {
        // Don't call parent::__construct() if we don't need the database connection immediately 
        // for this model itself (since it's a configuration model). 
        // But Model constructor helps with validation etc. 
        // However, we want to control the "loaded" logic.

        $this->initializeConfig();
    }

    private function initializeConfig()
    {
        $this->loaded = false;
        $configFile = ROOTPATH . 'config.php';

        if (file_exists($configFile)) {
            try {
                // Load the file
                $this->configData = require $configFile;

                // Set properties
                if (isset($this->configData['database']['DBPrefix'])) {
                    $this->db_prefix = $this->configData['database']['DBPrefix'];
                } elseif (isset($this->configData['db_prefix'])) {
                    $this->db_prefix = $this->configData['db_prefix'];
                }
                if (isset($this->configData['list_folder'])) {
                    $this->list_folder = $this->configData['list_folder'];
                }

                // Attempt Database Connection
                $db = \Config\Database::connect();

                // Forcing a connection check
                $db->initialize();

                // If no exception, we assume connected.
                $this->loaded = true;

            } catch (\Throwable $e) {
                // Log the error
                log_message('error', 'Config Model: Failed to load config or connect to DB. Error: ' . $e->getMessage());
                $this->loaded = false;
            }
        } else {
            log_message('warning', 'Config Model: config.php not found at ' . $configFile);
        }
    }
}
