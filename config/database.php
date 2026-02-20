<?php

// Adapter to use the existing config.php in root
$oldConfig = require __DIR__ . '/../config.php';

// If config.php doesn't exist or doesn't have database, fallback or error
if (!is_array($oldConfig) || !isset($oldConfig['database'])) {
    throw new \Exception("Configuration file missing or invalid.");
}

return $oldConfig['database'];
