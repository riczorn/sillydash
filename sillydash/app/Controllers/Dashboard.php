<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RecordModel;

class Dashboard extends BaseController
{
    private function getAllowedAccountIds()
    {
        if (session()->get('role') === 'admin') {
            return null; // All allowed
        }

        $allowedStr = session()->get('allowed_accounts');
        if (empty($allowedStr)) {
            return []; // None allowed
        }

        $domains = explode(',', $allowedStr);
        $db = \Config\Database::connect();
        $ids = $db->table('accounts')
            ->whereIn('domain', $domains)
            ->select('id')
            ->get()->getResultArray();

        return array_column($ids, 'id');
    }

    public function getChartData()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        $days = max(1, min(730, (int) ($this->request->getGet('days') ?? 30)));
        $accountDomain = $this->request->getGet('account');
        $db = \Config\Database::connect();
        $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));

        $recordsTable = $db->prefixTable('records');
        $filesTable = $db->prefixTable('files');

        // Check permissions
        $allowedIds = $this->getAllowedAccountIds();
        if ($allowedIds !== null && empty($allowedIds)) {
            return $this->response->setJSON([]);
        }

        // Resolve account filter
        $accountId = null;
        if ($accountDomain) {
            // Verify access
            if ($allowedIds !== null) {
                // We need to check if this domain's ID is in allowedIds
                $acc = $db->table('accounts')->where('domain', $accountDomain)->get()->getFirstRow('array');
                if (!$acc || !in_array((int) $acc['id'], $allowedIds, true)) {
                    return $this->response->setJSON(['error' => 'Access denied'])->setStatusCode(403);
                }
                $accountId = (int) $acc['id'];
            } else {
                // Admin
                $acc = $db->table('accounts')->where('domain', $accountDomain)->get()->getFirstRow('array');
                if ($acc) {
                    $accountId = (int) $acc['id'];
                } else {
                    return $this->response->setJSON([]);
                }
            }
        }

        // Helper to apply account filter
        $applyFilter = function ($builder, $col = 'account_id') use ($accountId, $allowedIds) {
            if ($accountId) {
                $builder->where($col, $accountId);
            } elseif ($allowedIds !== null) {
                $builder->whereIn($col, $allowedIds);
            }
        };

        $data = [];

        // 1. Accounts
        // Note: For "global view" (no accountId), admins see all accounts (path REGEXP...), users see specific accounts
        $builder = $db->table('records')
            ->select('DATE(time) as date, SUM(size_bytes) as value')
            ->where('kind', 'account')
            ->where('time >=', $startDate)
            ->groupBy('DATE(time)')
            ->orderBy('date', 'ASC');

        if ($accountId) {
            $builder->where('account_id', $accountId);
        } elseif ($allowedIds !== null) {
            // User with restricted accounts
            $builder->whereIn('account_id', $allowedIds);
        } else {
            // Admin global view - existing logic used path REGEXP, but account_id is cleaner if populated
            // Fallback to path REGEXP if account_id is unreliable, but for now assuming valid account_ids
            $builder->where('account_id >', 0);
        }

        $qAccounts = $builder->get()->getResultArray();
        foreach ($qAccounts as $row) {
            $data[] = ['date' => $row['date'], 'type' => 'accounts', 'value' => (int) $row['value']];
        }

        // 2. DB
        $builder = $db->table('records')
            ->select('DATE(time) as date, SUM(size_bytes) as value')
            ->where('kind', 'db')
            ->where('time >=', $startDate)
            ->groupBy('DATE(time)')
            ->orderBy('date', 'ASC');

        $applyFilter($builder, 'account_id');

        $qDb = $builder->get()->getResultArray();
        foreach ($qDb as $row) {
            $data[] = ['date' => $row['date'], 'type' => 'db', 'value' => (int) $row['value']];
        }

        // 3. Mail
        $mailBuilder = $db->table('records')
            ->select('file_id, SUM(size_bytes) as total_size');

        // For mail, we need to filter inner records
        $applyFilter($mailBuilder, 'account_id');

        // We also used LIKE paths in old code, but account_id should suffice if mapped correctly
        $mailSubquery = $mailBuilder
            ->whereIn('kind', ['mail']) // Ensure only mail records
            ->groupBy('file_id')
            ->getCompiledSelect();

        $qMail = $db->query("
            SELECT DATE(f.file_date) as date, MAX(t.total_size) as value
            FROM ($mailSubquery) as t
            JOIN $filesTable f ON f.id = t.file_id
            WHERE f.file_date >= ?
            GROUP BY DATE(f.file_date) ORDER BY date ASC
        ", [$startDate])->getResultArray();
        foreach ($qMail as $row) {
            $data[] = ['date' => $row['date'], 'type' => 'mail', 'value' => (int) $row['value']];
        }

        // 4. Spam
        $spamBuilder = $db->table('records')
            ->select('file_id, SUM(size_bytes) as total_size')
            ->where('kind', 'spam');
        $applyFilter($spamBuilder, 'account_id');
        $spamSubquery = $spamBuilder->groupBy('file_id')->getCompiledSelect();

        $qSpam = $db->query("
            SELECT DATE(f.file_date) as date, MAX(t.total_size) as value
            FROM ($spamSubquery) as t
            JOIN $filesTable f ON f.id = t.file_id
            WHERE f.file_date >= ?
            GROUP BY DATE(f.file_date) ORDER BY date ASC
        ", [$startDate])->getResultArray();
        foreach ($qSpam as $row) {
            $data[] = ['date' => $row['date'], 'type' => 'spam', 'value' => (int) $row['value']];
        }

        // 5. Log
        $logBuilder = $db->table('records')
            ->select('file_id, SUM(size_bytes) as total_size')
            ->where('kind', 'log');
        $applyFilter($logBuilder, 'account_id');
        $logSubquery = $logBuilder->groupBy('file_id')->getCompiledSelect();

        $qLog = $db->query("
            SELECT DATE(f.file_date) as date, MAX(t.total_size) as value
            FROM ($logSubquery) as t
            JOIN $filesTable f ON f.id = t.file_id
            WHERE f.file_date >= ?
            GROUP BY DATE(f.file_date) ORDER BY date ASC
        ", [$startDate])->getResultArray();
        foreach ($qLog as $row) {
            $data[] = ['date' => $row['date'], 'type' => 'log', 'value' => (int) $row['value']];
        }

        return $this->response->setJSON($data);
    }

    public function getChartDetail()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        $date = $this->request->getGet('date');
        $type = $this->request->getGet('type');

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setJSON(['error' => 'Invalid date format'])->setStatusCode(400);
        }

        $validTypes = ['accounts', 'mail', 'db', 'spam', 'log'];
        if (!$type || !in_array($type, $validTypes, true)) {
            return $this->response->setJSON(['error' => 'Invalid type'])->setStatusCode(400);
        }

        // Permissions
        $allowedIds = $this->getAllowedAccountIds();
        if ($allowedIds !== null && empty($allowedIds)) {
            return $this->response->setJSON([]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('records r');
        $builder->select('a.domain, SUM(r.size_bytes) as size');
        $builder->join('accounts a', 'a.id = r.account_id');
        $builder->where('DATE(r.time)', $date);

        if ($allowedIds !== null) {
            $builder->whereIn('r.account_id', $allowedIds);
        }

        // Map 'accounts' type to 'account' kind
        $kind = ($type === 'accounts') ? 'account' : $type;
        $builder->where('r.kind', $kind);

        $builder->groupBy('r.account_id');
        $builder->orderBy('size', 'DESC');
        $builder->limit(10);

        $results = $builder->get()->getResultArray();

        return $this->response->setJSON($results);
    }

    public function getSubdomainData()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        $accountDomain = $this->request->getGet('account');
        $days = max(1, min(730, (int) ($this->request->getGet('days') ?? 30)));

        if (!$accountDomain) {
            return $this->response->setJSON([]);
        }

        // Permissions
        $allowedIds = $this->getAllowedAccountIds();
        if ($allowedIds !== null) {
            // Check if accountDomain is allowed
            $db = \Config\Database::connect();
            $acc = $db->table('accounts')->where('domain', $accountDomain)->get()->getFirstRow('array');
            if (!$acc || !in_array((int) $acc['id'], $allowedIds, true)) {
                return $this->response->setJSON(['error' => 'Access denied'])->setStatusCode(403);
            }
        }

        $db = \Config\Database::connect();
        $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        $recordsTable = $db->prefixTable('records');
        $accountsTable = $db->prefixTable('accounts');

        // Find the parent account
        $parent = $db->table('accounts')->where('domain', $accountDomain)->get()->getFirstRow('array');
        if (!$parent) {
            return $this->response->setJSON([]);
        }
        $parentHome = $parent['home_directory'];

        // Get all subdomain paths under this account over time
        $rows = $db->query("
            SELECT DATE(r.time) as date,
                   REPLACE(r.path, ?, '') as subdomain,
                   r.size_bytes as value
            FROM {$recordsTable} r
            WHERE r.kind = 'account'
              AND r.path LIKE ?
              AND r.path != ?
              AND r.time >= ?
            ORDER BY date ASC, r.size_bytes DESC
        ", [
            $parentHome . '/domains/',
            $parentHome . '/domains/%',
            $parentHome,
            $startDate
        ])->getResultArray();

        // Cast values to int
        foreach ($rows as &$row) {
            $row['value'] = (int) $row['value'];
        }

        return $this->response->setJSON($rows);
    }
}
