<?php

namespace App\Controllers;

use App\Models\Config;

class Home extends BaseController
{
    public function index()
    {
        // 0. Check for Config Existence
        if (!file_exists(ROOTPATH . 'config.php')) {
            return redirect()->to('setup');
        }

        // 1. Load Config Model
        $configModel = new Config();

        // 2. Check Authentication
        if (session()->get('isLoggedIn')) {
            $accountModel = new \App\Models\AccountModel();

            $accounts = [];
            $allowed = [];
            if (session()->get('role') === 'admin') {
                $accounts = $accountModel->getAccountsWithSummary();
            } else {
                $allowedStr = session()->get('allowed_accounts');
                if (!empty($allowedStr)) {
                    $allowed = explode(',', $allowedStr);
                    $accounts = $accountModel->getAccountsWithSummary($allowed);
                }
            }

            // Sanitize and verify account filter from GET parameter
            $account = $this->request->getGet('account');
            if ($account) {
                $account = preg_replace('/[^a-zA-Z0-9.\-]/', '', $account);

                // If restricted user, verify account is allowed
                if (session()->get('role') !== 'admin' && !in_array($account, $allowed)) {
                    $account = null;
                }
            }

            $data = [
                'accounts' => $accounts,
                'account' => $account ?: null,
                'subdomainData' => $account ? $accountModel->getSubdomainBreakdown($account) : [],
            ];
            return view('dashboard', $data);
        }

        // 3. Prepare Status Data (Guest View)
        $data = [
            'configLoaded' => $configModel->loaded,
            'dbConnected' => $configModel->loaded,
            'folderReadable' => false,
            'listFolder' => $configModel->list_folder,
        ];

        // 4. Check list_folder
        if (is_dir($configModel->list_folder) && is_readable($configModel->list_folder)) {
            $data['folderReadable'] = true;
        }

        return view('guest', $data);
    }
}
