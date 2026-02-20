<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AccountModel;
use App\Models\RecordModel;

class Accounts extends Controller
{
    public function index()
    {
        if (!$this->getSession('isLoggedIn')) {
            $this->redirect(site_url('login'));
        }

        $allowed = [];
        // If not admin, restrict to allowed accounts
        if ($this->getSession('role') !== 'admin') {
            $allowedStr = $this->getSession('allowed_accounts');
            if (!empty($allowedStr)) {
                $allowed = explode(',', $allowedStr);
            } else {
                // User has no allowed accounts -> show nothing
                $this->view('accounts/index', ['accounts' => []]);
                return;
            }
        }

        $accountModel = new AccountModel();
        // getAccountsWithSummary returns array of arrays
        $flatAccounts = $accountModel->getAccountsWithSummary($allowed);

        // 1. Index by ID and build children list
        $accountsById = [];
        $childrenByParent = [];
        foreach ($flatAccounts as $acc) {
            $accountsById[$acc['id']] = $acc;
            $pid = $acc['parent_id'] ?? 0;
            if (!isset($childrenByParent[$pid])) {
                $childrenByParent[$pid] = [];
            }
            $childrenByParent[$pid][] = $acc['id'];
        }

        // Sort children by domain ASC
        foreach ($childrenByParent as $pid => &$children) {
            usort($children, function ($a, $b) use ($accountsById) {
                return strcasecmp($accountsById[$a]['domain'], $accountsById[$b]['domain']);
            });
        }
        unset($children);

        // 2. Flatten into tree order (DFS)
        $hierarchicalAccounts = [];
        $processNode = function ($parentId, $depth) use (&$processNode, &$accountsById, &$childrenByParent, &$hierarchicalAccounts) {
            if (!isset($childrenByParent[$parentId])) {
                return;
            }
            foreach ($childrenByParent[$parentId] as $childId) {
                if (isset($accountsById[$childId])) {
                    $acc = $accountsById[$childId];
                    $acc['depth'] = $depth;
                    $acc['has_children'] = isset($childrenByParent[$childId]);
                    $acc['children_count'] = $acc['has_children'] ? count($childrenByParent[$childId]) : 0;
                    $hierarchicalAccounts[] = $acc;

                    // Recurse
                    $processNode($childId, $depth + 1);
                }
            }
        };

        // Start with root nodes (parent_id == 0 or null)
        // Root nodes are in $childrenByParent[0] (already sorted above)
        if (isset($childrenByParent[0])) {
            $processNode(0, 0);
        }

        // Fetch unmatched paths
        $recordModel = new RecordModel();
        $unmatchedPaths = [];
        if ($this->getSession('role') === 'admin') {
            $unmatchedPaths = $recordModel->getUnmatchedPaths();
        }

        $this->view('accounts/index', [
            'accounts' => $hierarchicalAccounts,
            'unmatched' => $unmatchedPaths,
            'flatAccounts' => $flatAccounts // To be used in the parent dropdown
        ]);
    }

    public function assign()
    {
        if ($this->getSession('role') !== 'admin') {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $path = $_POST['path'] ?? '';
        $domain = $_POST['domain'] ?? '';
        $action = $_POST['action'] ?? ''; // 'create' or 'link'
        $parentId = $_POST['parent_id'] ?? null;

        if (empty($path) || empty($domain) || empty($action)) {
            return $this->json(['success' => false, 'error' => 'Missing required fields'], 400);
        }

        $accountModel = new AccountModel();

        // 1. Create the new account mapping
        $data = [
            'domain' => $domain,
            'home_directory' => $path,
            'username' => basename($path), // Guess username from path
        ];

        if ($action === 'link' && !empty($parentId)) {
            $data['parent_id'] = $parentId;
        }

        $accountId = $accountModel->insert($data);

        if (!$accountId) {
            return $this->json(['success' => false, 'error' => 'Failed to create account database record'], 500);
        }

        // 2. Update existing records in the database natively
        // Update all records whose path starts with the new home_directory (to map everything underneath)
        $db = \App\Core\Database::getInstance();
        $prefix = $db->getPrefix();
        $sql = "UPDATE {$prefix}records SET account_id = ? WHERE account_id IS NULL AND (path = ? OR path LIKE ?)";
        $db->query($sql, [$accountId, $path, $path . '/%']);

        return $this->json(['success' => true]);
    }
}
