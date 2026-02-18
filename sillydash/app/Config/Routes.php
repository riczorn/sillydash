<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Auth Routes
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');
$routes->get('change-password', 'Auth::changePassword');
$routes->post('change-password', 'Auth::attemptChangePassword');

// Setup Routes
$routes->get('setup', 'Setup::index');
$routes->post('setup/attempt', 'Setup::attemptSetup');

// Settings (admin-only)
$routes->get('settings', 'Settings::index', ['filter' => 'auth']);
$routes->post('settings/save', 'Settings::save', ['filter' => 'auth']);
$routes->post('settings/test-email', 'Settings::testEmail', ['filter' => 'auth']);

$routes->get('cron', 'Cron::index');
$routes->get('accounts', 'Accounts::index');
$routes->get('users', 'Users::index');
$routes->get('users/create', 'Users::create', ['filter' => 'auth']);
$routes->post('users/store', 'Users::store', ['filter' => 'auth']);
$routes->get('users/edit/(:num)', 'Users::edit/$1', ['filter' => 'auth']);
$routes->post('users/update/(:num)', 'Users::update/$1', ['filter' => 'auth']);
$routes->get('api/chart-data', 'Dashboard::getChartData');
$routes->get('api/chart-detail', 'Dashboard::getChartDetail');
$routes->get('api/subdomain-data', 'Dashboard::getSubdomainData');
