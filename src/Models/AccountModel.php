<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class AccountModel extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['parent_id', 'domain', 'username', 'home_directory', 'db_names'];

    public function getTotalSize()
    {
        $prefix = $this->db->getPrefix();
        $recordsTable = $prefix . 'records';
        $sql = "SELECT SUM(size_bytes) as total FROM {$recordsTable}";
        $result = $this->db->fetch($sql);
        return $result['total'] ?? 0;
    }

    /**
     * Finds an account based on a path or database name.
     * 
     * @param string $path The file path (e.g., /home/user/domains/sub.example.com)
     * @param string $dbName The database name
     * @return array|null The account row or null
     */
    public function findDomainByPathOrDb($path = null, $dbName = null)
    {
        if ($path) {
            // Check for log files (format: [date time] domain_access_log)
            if (preg_match('/(?:[0-9]{4}-[0-9]{2}-[0-9]{2}\s+[0-9]{2}:[0-9]{2}\s+)?([^\s]+)_(?:access|error)_log/', $path, $m)) {
                $logDomain = $m[1];
                $sql = "SELECT * FROM {$this->table} WHERE domain = ?";
                $logMatch = $this->db->fetch($sql, [$logDomain]);
                if ($logMatch) {
                    return $logMatch;
                }
            }

            // 1. Exact match on home_directory
            $sql = "SELECT * FROM {$this->table} WHERE home_directory = ?";
            $account = $this->db->fetch($sql, [$path]);
            if ($account)
                return $account;

            // 2. Try matching a sub-server home_directory (path may be a child directory)
            $sql = "SELECT * FROM {$this->table}
                    WHERE ? LIKE CONCAT(home_directory, '/%')
                    ORDER BY LENGTH(home_directory) DESC
                    LIMIT 1";
            $prefixMatch = $this->db->fetch($sql, [$path]);
            if ($prefixMatch)
                return $prefixMatch;

            // 3. Fallback: extract /home/username from path and match parent account
            if (preg_match('#^(/home/[^/]+)#', $path, $m)) {
                $parentHome = $m[1];
                $sql = "SELECT * FROM {$this->table} WHERE home_directory = ?";
                $parentMatch = $this->db->fetch($sql, [$parentHome]);
                if ($parentMatch)
                    return $parentMatch;
            }
        }

        if ($dbName) {
            // db_names is a comma-separated string
            $sql = "SELECT * FROM {$this->table}
                    WHERE FIND_IN_SET(?, TRIM(TRAILING ',' FROM db_names)) > 0
                    LIMIT 1";
            $result = $this->db->fetch($sql, [$dbName]);
            if ($result) {
                return $result;
            }

            // Fallback: Check if the db name matches the domain name prefix
            $sql = "SELECT * FROM {$this->table}
                    WHERE domain LIKE CONCAT(?, '.%') OR domain = ?
                    ORDER BY LENGTH(domain) DESC LIMIT 1";
            $fallbackResult = $this->db->fetch($sql, [$dbName, $dbName]);
            if ($fallbackResult) {
                return $fallbackResult;
            }
        }

        return null;
    }

    /**
     * Retrieves accounts with aggregated disk usage from records.
     * @param array $allowedDomains List of domains to filter by (empty = all)
     */
    public function getAccountsWithSummary(array $allowedDomains = [])
    {
        $recordsTable = $this->db->getPrefix() . 'records';

        // Select accounts and aggregate size
        // We use 'left' join to include accounts with no records (size 0)
        $sql = "SELECT {$this->table}.*, SUM(r.size_bytes) as total_size, COUNT(r.id) as record_count
                FROM {$this->table}
                LEFT JOIN {$recordsTable} r ON r.account_id = {$this->table}.id";

        $params = [];
        if (!empty($allowedDomains)) {
            $placeholders = implode(',', array_fill(0, count($allowedDomains), '?'));
            $sql .= " WHERE {$this->table}.domain IN ($placeholders)";
            $params = $allowedDomains;
        }

        $sql .= " GROUP BY {$this->table}.id ORDER BY total_size DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Retrieves subdomain breakdown (size by kind) for an account on the latest date.
     *
     * @param string $domain The account domain
     * @return array Pivoted data: [subdomain => [account => X, mail => Y, ...]]
     */
    public function getSubdomainBreakdown(string $domain): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE domain = ?";
        $acct = $this->db->fetch($sql, [$domain]);

        if (!$acct) {
            return [];
        }

        $parentHome = $acct['home_directory'];

        $recordsTable = $this->db->getPrefix() . 'records';
        // $this->table is already prefixed 'accounts'

        $sql = "
            SELECT
                REPLACE(r.path, ?, '') as subdomain,
                r.kind,
                SUM(r.size_bytes) as size
            FROM {$recordsTable} r
            WHERE r.account_id IN (
                SELECT id FROM {$this->table} WHERE home_directory LIKE ? OR home_directory = ?
            )
            AND DATE(r.time) = (SELECT MAX(DATE(time)) FROM {$recordsTable})
            AND r.path LIKE ?
            GROUP BY subdomain, r.kind
            ORDER BY size DESC
        ";

        $params = [
            $parentHome . '/domains/',
            $parentHome . '/domains/%',
            $parentHome,
            $parentHome . '/%'
        ];

        $rows = $this->db->fetchAll($sql, $params);

        // Pivot data: subdomain => [account => X, mail => Y, ...]
        $pivoted = [];
        foreach ($rows as $row) {
            $sub = $row['subdomain'] ?: basename($parentHome);
            if (!isset($pivoted[$sub])) {
                $pivoted[$sub] = ['account' => 0, 'mail' => 0, 'db' => 0, 'spam' => 0, 'log' => 0];
            }
            if (isset($pivoted[$sub][$row['kind']])) {
                $pivoted[$sub][$row['kind']] += (int) $row['size'];
            }
        }

        uasort($pivoted, fn($a, $b) => $b['account'] - $a['account']);

        return $pivoted;
    }

    /**
     * Get all top-level accounts (no parent).
     */
    public function getTopLevelAccounts()
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE parent_id = 0 OR parent_id IS NULL
                ORDER BY domain ASC";
        return $this->db->fetchAll($sql);
    }
}
