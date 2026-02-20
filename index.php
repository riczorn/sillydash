<?php

// Check PHP version
if (version_compare(phpversion(), '7.4', '<')) {
    die("Use PHP 7.4 or higher.");
}

// Define paths
define('ROOTPATH', __DIR__ . '/');
define('APPPATH', ROOTPATH . 'src/');
define('CONFIGPATH', ROOTPATH . 'config/');



// Custom Autoloader
require APPPATH . 'Core/Autoloader.php';
$loader = new \App\Core\Autoloader();
$loader->register();
$loader->addNamespace('App', APPPATH);

// Load Helper Functions
require APPPATH . 'Core/functions.php';

// Check if config exists, if not redirect to setup
if (!file_exists(ROOTPATH . 'config.php')) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    // Allow setup routes and assets
    // We check if the URI contains 'setup' or 'assets'. 
    // This is a simple check; stricter checking might be needed if complex routes exist.
    if (strpos($requestUri, '/setup') === false && strpos($requestUri, '/assets') === false) {
        header('Location: ' . base_url('setup'));
        exit;
    }
}

// Check environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

use App\Core\Router;

$router = new Router();

// Load Routes
require CONFIGPATH . 'routes.php';

// Dispatch
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);
