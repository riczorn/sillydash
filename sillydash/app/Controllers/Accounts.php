<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountModel;

class Accounts extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $allowed = [];
        // If not admin, restrict to allowed accounts
        if (session()->get('role') !== 'admin') {
            $allowedStr = session()->get('allowed_accounts');
            if (!empty($allowedStr)) {
                $allowed = explode(',', $allowedStr);
            } else {
                // User has no allowed accounts -> show nothing
                return view('accounts/index', ['accounts' => []]);
            }
        }

        $accountModel = new AccountModel();
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
        $processNode(0, 0);

        $data = [
            'accounts' => $hierarchicalAccounts,
        ];

        return view('accounts/index', $data);
    }
}
