<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RecordModel;

class Dashboard extends Controller
{
    private function getAllowedAccountIds()
    {
        if ($this->getSession('role') === 'admin') {
            return null; // All allowed
        }

        $allowedStr = $this->getSession('allowed_accounts');
        if (empty($allowedStr)) {
            return []; // None allowed
        }

        $domains = explode(',', $allowedStr);
        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($domains), '?'));

        $prefix = $this->db->getPrefix();
        $accountsTable = $prefix . 'accounts';

        $sql = "SELECT id FROM {$accountsTable} WHERE domain IN ($placeholders)";
        $ids = $this->db->fetchAll($sql, $domains);

        return array_column($ids, 'id');
    }

    public function getChartData()
    {
        $days = max(1, min(730, (int) ($this->getQuery('days') ?? 30)));
        $accountDomain = trim($this->getQuery('account') ?? '');

        $isLoggedIn = $this->getSession('isLoggedIn') ? 'yes' : 'no';
        $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));

        file_put_contents('/tmp/debug_chart.log', "Request: days=$days acc=$accountDomain LoggedIn=$isLoggedIn StartDate=$startDate\n", FILE_APPEND);

        if (!$this->getSession('isLoggedIn')) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Check permissions
        $allowedIds = $this->getAllowedAccountIds();
        if ($allowedIds !== null && empty($allowedIds)) {
            return $this->json([]);
        }

        $prefix = $this->db->getPrefix();
        $recordsTable = $prefix . 'records';
        $accountsTable = $prefix . 'accounts';

        // Resolve account filter
        $accountId = null;
        if ($accountDomain) {
            $sql = "SELECT id FROM {$accountsTable} WHERE domain = ?";
            $acc = $this->db->fetch($sql, [$accountDomain]);

            if ($allowedIds !== null) {
                if (!$acc || !in_array((int) $acc['id'], $allowedIds, true)) {
                    return $this->json(['error' => 'Access denied'], 403);
                }
                $accountId = (int) $acc['id'];
            } else {
                // Admin
                if ($acc) {
                    $accountId = (int) $acc['id'];
                } else {
                    file_put_contents('/tmp/debug_chart.log', "Account not found in DB\n", FILE_APPEND);
                    return $this->json([]);
                }
            }
        }
        file_put_contents('/tmp/debug_chart.log', "Resolved AccountID: " . ($accountId ?? 'NULL') . "\n", FILE_APPEND);

        // Helper to build WHERE clause
        $buildWhere = function ($baseWhere, &$params) use ($accountId, $allowedIds) {
            $where = $baseWhere;
            if ($accountId) {
                $where .= " AND account_id = ?";
                $params[] = $accountId;
            } elseif ($allowedIds !== null) {
                $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
                $where .= " AND account_id IN ($placeholders)";
                $params = array_merge($params, $allowedIds);
            } else {
                // Admin global view - existing logic used path REGEXP or > 0
                $where .= " AND account_id > 0";
            }
            return $where;
        };

        $data = [];

        // 1. Accounts
        $params = [$startDate];
        $where = $buildWhere("WHERE kind = 'account' AND time >= ?", $params);
        $sql = "SELECT DATE(time) as date, SUM(size_bytes) as value 
                FROM {$recordsTable} 
                $where 
                GROUP BY DATE(time) 
                ORDER BY date ASC";

        $qAccounts = $this->db->fetchAll($sql, $params);
        file_put_contents('/tmp/debug_chart.log', "Accounts Query: " . count($qAccounts) . " rows\n", FILE_APPEND);
        foreach ($qAccounts as $row) {
            $data[] = ['date' => $row['date'], 'type' => 'accounts', 'value' => (int) $row['value']];
        }

        // 2. DB
        $params = [$startDate];
        $where = $buildWhere("WHERE kind = 'db' AND time >= ?", $params);
        $sql = "SELECT DATE(time) as date, SUM(size_bytes) as value 
                FROM {$recordsTable} 
                $where 
                GROUP BY DATE(time) 
                ORDER BY date ASC";

        $qDb = $this->db->fetchAll($sql, $params);
        foreach ($qDb as $row) {
            $data[] = ['date' => $row['date'], 'type' => 'db', 'value' => (int) $row['value']];
        }

        // Helper for file-based records (mail, spam, log)
        $processFileBased = function ($kind) use ($startDate, $accountId, $allowedIds, &$data) {
            $params = [$kind];
            // Inner query to sum size by file_id for the specific kind
            $baseWhere = "WHERE kind = ?";

            // Apply account filters to the inner query
            $where = $baseWhere;
            if ($accountId) {
                $where .= " AND account_id = ?";
                $params[] = $accountId;
            } elseif ($allowedIds !== null) {
                $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
                $where .= " AND account_id IN ($placeholders)";
                $params = array_merge($params, $allowedIds);
            }

            // We need to group by file_id first to get total size per file
            // Then join with files table to get date

            // BUT, we can simplify if we just join directly?
            // "SELECT DATE(f.file_date) as date, SUM(r.size_bytes) as value FROM records r JOIN files f ON f.id = r.file_id ..."
            // Wait, the original code had a subquery:
            // SELECT DATE(f.file_date) as date, MAX(t.total_size) as value FROM ($mailSubquery) as t JOIN files f ...
            // Ah, because a file has multiple records (one per account per kind)? 
            // No, a file is a snapshot.
            // Original query: 
            /*
               $mailSubquery = $mailBuilder->where('kind', 'mail')->groupBy('file_id')...
               SELECT DATE(f.file_date)... MAX(t.total_size) ...
            */
            // It seems it sums up size for a file_id, then takes MAX? 
            // Logic: For a given date (file), we want the sum of sizes of all mail records in that file.
            // If we have multiple files per day, we sum them? No, group by DATE(file_date).
            // Original: MAX(t.total_size). This suggests if there are multiple files per day, it takes the max? 
            // Or maybe file_date is unique per day? usually yes.

            // Let's replicate logic:
            // 1. Calculate total size for each file_id for the given kind and account filters.
            // 2. Join with files to get date.
            // 3. Group by date.

            // Subquery construction
            // We can't easily build subquery string with placeholders mixed without careful handling.
            // Let's do a join directly.

            $prefix = $this->db->getPrefix();
            $recordsIn = $prefix . 'records';
            $filesTable = $prefix . 'files';

            $sql = "SELECT DATE(f.file_date) as date, SUM(r.size_bytes) as value
                     FROM {$recordsIn} r
                     JOIN {$filesTable} f ON f.id = r.file_id
                     $where
                     AND f.file_date >= ?
                     GROUP BY DATE(f.file_date)
                     ORDER BY date ASC";

            // Wait, original used MAX(total_size) where total_size = SUM(size_bytes) per file_id.
            // If we Group By DATE(file_date), and if there is only 1 file per date, SUM is fine.
            // If there are multiple files per date (e.g. run twice), SUM would add them up (double counting if cumulative).
            // Records are snapshots. If we run twice a day, we have 2 sets of records.
            // We probably want the LATEST file for the date, or just MAX size?
            // Original logic: `FROM ($mailSubquery) as t ... GROUP BY DATE(file_date) ... MAX(t.total_size)`
            // This implies if multiple files exist for a date, take the one with largest size (likely the latest/most complete).

            // To implement strictly:
            // 1. Get Sum per file_id
            // 2. Wrap and Group by Date taking Max

            $innerSql = "SELECT r.file_id, SUM(r.size_bytes) as total_size
                          FROM {$recordsIn} r
                          $where
                          GROUP BY r.file_id";

            $finalSql = "SELECT DATE(f.file_date) as date, MAX(t.total_size) as value
                          FROM ($innerSql) as t
                          JOIN {$filesTable} f ON f.id = t.file_id
                          WHERE f.file_date >= ?
                          GROUP BY DATE(f.file_date)
                          ORDER BY date ASC";

            $params[] = $startDate; // Add startDate to params

            $rows = $this->db->fetchAll($finalSql, $params);
            foreach ($rows as $row) {
                $data[] = ['date' => $row['date'], 'type' => $kind, 'value' => (int) $row['value']];
            }
        };

        $processFileBased('mail');
        $processFileBased('spam');
        $processFileBased('log');

        return $this->json($data);
    }

    public function getChartDetail()
    {
        if (!$this->getSession('isLoggedIn')) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $date = $this->getQuery('date');
        $type = $this->getQuery('type');

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['error' => 'Invalid date format'], 400);
        }

        $validTypes = ['accounts', 'mail', 'db', 'spam', 'log'];
        if (!$type || !in_array($type, $validTypes, true)) {
            return $this->json(['error' => 'Invalid type'], 400);
        }

        // Permissions
        $allowedIds = $this->getAllowedAccountIds();
        if ($allowedIds !== null && empty($allowedIds)) {
            return $this->json([]);
        }

        $kind = ($type === 'accounts') ? 'account' : $type;
        $params = [$date, $kind];

        $where = "";
        if ($allowedIds !== null) {
            $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
            $where = "AND r.account_id IN ($placeholders)";
            $params = array_merge($params, $allowedIds);
        }

        $prefix = $this->db->getPrefix();
        $recordsTable = $prefix . 'records';
        $accountsTable = $prefix . 'accounts';

        $sql = "SELECT a.domain, SUM(r.size_bytes) as size
                FROM {$recordsTable} r
                JOIN {$accountsTable} a ON a.id = r.account_id
                WHERE DATE(r.time) = ?
                AND r.kind = ?
                $where
                GROUP BY r.account_id
                ORDER BY size DESC
                LIMIT 10";

        $results = $this->db->fetchAll($sql, $params);
        return $this->json($results);
    }

    public function getSubdomainData()
    {
        if (!$this->getSession('isLoggedIn')) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $accountDomain = $this->getQuery('account');
        $days = max(1, min(730, (int) ($this->getQuery('days') ?? 30)));

        if (!$accountDomain) {
            return $this->json([]);
        }

        // Permissions
        $prefix = $this->db->getPrefix();
        $accountsTable = $prefix . 'accounts';

        $allowedIds = $this->getAllowedAccountIds();
        if ($allowedIds !== null) {
            $sql = "SELECT id FROM {$accountsTable} WHERE domain = ?";
            $acc = $this->db->fetch($sql, [$accountDomain]);
            if (!$acc || !in_array((int) $acc['id'], $allowedIds, true)) {
                return $this->json(['error' => 'Access denied'], 403);
            }
        }

        $startDate = date('Y-m-d H:i:s', strtotime("-$days days"));

        $sql = "SELECT * FROM {$accountsTable} WHERE domain = ?";
        $parent = $this->db->fetch($sql, [$accountDomain]);

        if (!$parent) {
            return $this->json([]);
        }
        $parentHome = $parent['home_directory'];

        $prefix = $this->db->getPrefix();
        $recordsTable = $prefix . 'records';
        $accountsTable = $prefix . 'accounts';

        // Get all subdomain paths under this account over time
        $sql = "SELECT DATE(r.time) as date,
                       REPLACE(r.path, ?, '') as subdomain,
                       r.size_bytes as value
                FROM {$recordsTable} r
                WHERE r.kind = 'account'
                  AND r.path LIKE ?
                  AND r.path != ?
                  AND r.time >= ?
                ORDER BY date ASC, r.size_bytes DESC";

        $params = [
            $parentHome . '/domains/',
            $parentHome . '/domains/%',
            $parentHome,
            $startDate
        ];

        $rows = $this->db->fetchAll($sql, $params);

        foreach ($rows as &$row) {
            $row['value'] = (int) $row['value'];
        }

        return $this->json($rows);
    }

    public function getAccountsDistribution()
    {
        if (!$this->getSession('isLoggedIn')) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Permissions
        $allowedIds = $this->getAllowedAccountIds();
        if ($allowedIds !== null && empty($allowedIds)) {
            return $this->json([]);
        }

        $prefix = $this->db->getPrefix();
        $recordsTable = $prefix . 'records';
        $accountsTable = $prefix . 'accounts';

        // 1. Find the latest date in records to ensure we get current state
        $sqlDate = "SELECT MAX(DATE(time)) as max_date FROM {$recordsTable} WHERE kind = 'account'";
        $rowDate = $this->db->fetch($sqlDate);
        $maxDate = $rowDate['max_date'] ?? date('Y-m-d');

        // 2. Fetch usage per account for that date
        $params = [$maxDate];
        $where = "";

        if ($allowedIds !== null) {
            $placeholders = implode(',', array_fill(0, count($allowedIds), '?'));
            $where = "AND r.account_id IN ($placeholders)";
            $params = array_merge($params, $allowedIds);
        }

        $sql = "SELECT a.domain, SUM(r.size_bytes) as value
                FROM {$recordsTable} r
                JOIN {$accountsTable} a ON a.id = r.account_id
                WHERE DATE(r.time) = ?
                AND r.kind = 'account'
                AND (a.parent_id IS NULL OR a.parent_id = 0)
                $where
                GROUP BY r.account_id
                ORDER BY value DESC";

        $rows = $this->db->fetchAll($sql, $params);

        if (empty($rows)) {
            return $this->json([]);
        }

        // 3. Process Top 15 + Others
        $top15 = array_slice($rows, 0, 15);
        $others = array_slice($rows, 15);

        $result = [];
        foreach ($top15 as $row) {
            $result[] = [
                'label' => $row['domain'],
                'value' => (int) $row['value']
            ];
        }

        if (!empty($others)) {
            $othersSum = 0;
            foreach ($others as $row) {
                $othersSum += (int) $row['value'];
            }
            $result[] = [
                'label' => 'Others (' . count($others) . ')',
                'value' => $othersSum,
                'is_others' => true
            ];
        }

        return $this->json($result);
    }
}
