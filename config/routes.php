<?php

/** @var \App\Core\Router $router */

$router->get('/', 'Home::index');

// Auth Routes
// Auth Routes
$router->post('/attempt-login', 'Auth::attemptLogin');
$router->get('/logout', 'Auth::logout');
$router->get('/change-password', 'Auth::changePassword');
$router->post('/change-password', 'Auth::attemptChangePassword');

// Setup Routes
$router->get('/setup', 'Setup::index');
$router->post('/setup/attempt', 'Setup::attemptSetup');

// Settings (admin-only)
$router->get('/settings', 'Settings::index');
$router->post('/settings/save', 'Settings::save');
$router->post('/settings/test-email', 'Settings::testEmail');

$router->get('/cron', 'Cron::index');
$router->get('/accounts', 'Accounts::index');
$router->post('/accounts/assign', 'Accounts::assign');
$router->get('/users', 'Users::index');
$router->get('/users/create', 'Users::create');
$router->post('/users/store', 'Users::store');
$router->get('/users/edit/(:num)', 'Users::edit');
$router->post('/users/update/(:num)', 'Users::update');

$router->get('/api/chart-data', 'Dashboard::getChartData');
$router->get('/api/chart-detail', 'Dashboard::getChartDetail');
$router->get('/api/subdomain-data', 'Dashboard::getSubdomainData');
$router->get('/api/accounts-distribution', 'Dashboard::getAccountsDistribution');
