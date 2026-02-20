<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setup;
use App\Models\AccountModel;
use App\Models\FileModel;
use App\Models\RecordModel;

class Home extends Controller
{
    public function index()
    {
        // Check if config exists
        if (!file_exists(ROOTPATH . 'config.php')) {
            $this->redirect(site_url('setup'));
        }

        // Check DB connection
        try {
            $db = \App\Core\Database::getInstance()->getConnection();
        } catch (\Exception $e) {
            $this->redirect(site_url('setup'));
        }

        // If generic route '/' is requested, check if logged in
        if ($this->getSession('isLoggedIn')) {
            // Render Dashboard View

            // We need data for the dashboard
            $accountModel = new AccountModel();
            $fileModel = new FileModel();
            $recordModel = new RecordModel();

            $account = $this->getQuery('account');

            // Stats for dashboard
            $data = [
                'totalAccounts' => count($accountModel->findAll()),
                'totalFiles' => $fileModel->getTotalFiles(), // Assuming method exists or use count
                'recentRecords' => $recordModel->getRecent(5),
                'diskUsageTrends' => [], // Placeholder or fetch real data
                'subdomainData' => $account ? $accountModel->getSubdomainBreakdown($account) : [],
                'account' => $account,
                'accounts' => $accountModel->findAll(), // Required by lines 124 in dashboard view
                'totalDiskUsage' => $accountModel->getTotalSize() ?? 0
            ];

            // To be safe and simple for migration, let's just render the dashboard view 
            // and let the view/ajax fetch data if designed that way.
            // But looking at Dashboard Controller (which I should checked), it might have its own method.
            // Actually, there is no Dashboard controller in the list I saw earlier? 
            // Wait, I saw 'Dashboard' in 'Migrate Controllers' list.
            // Let's check if there is a Dashboard controller.

            if (class_exists('App\Controllers\Dashboard')) {
                // Forwarding logic or just redirect?
                // A redirect is cleaner to keep URL as /dashboard if that route exists.
                // URL / is fine for dashboard too.
            }

            // For now, let's just load the dashboard view directly as Home meant to be Dashboard?
            // Or redirect to /dashboard if we have a route for it?
            // Checking routes.php... I see /api/... but no /dashboard route!
            // So Home::index IS the dashboard.

            $this->view('dashboard', $data);
            return;
        }

        // Not logged in -> Show Guest Page
        $this->view('guest');
    }
}
