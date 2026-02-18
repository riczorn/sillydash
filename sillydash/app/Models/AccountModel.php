<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountModel extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['parent_id', 'domain', 'username', 'home_directory', 'db_names'];

    protected $useTimestamps = false;

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
            // 1. Exact match on home_directory
            $account = $this->where('home_directory', $path)->first();
            if ($account)
                return $account;

            // 2. Try matching a sub-server home_directory (path may be a child directory)
            // e.g. path=/home/aconite/domains/ozium-azza.it/homes
            //      might match home_directory=/home/aconite/domains/ozium-azza.it
            $db = \Config\Database::connect();
            $table = $db->prefixTable('accounts');
            $prefixMatch = $db->query("
                SELECT * FROM {$table}
                WHERE ? LIKE CONCAT(home_directory, '/%')
                ORDER BY LENGTH(home_directory) DESC
                LIMIT 1
            ", [$path])->getRowArray();
            if ($prefixMatch)
                return $prefixMatch;

            // 3. Fallback: extract /home/username from path and match parent account
            if (preg_match('#^(/home/[^/]+)#', $path, $m)) {
                $parentHome = $m[1];
                $parentMatch = $this->where('home_directory', $parentHome)->first();
                if ($parentMatch)
                    return $parentMatch;
            }
        }

        if ($dbName) {
            // db_names is a comma-separated string (e.g., "db1,db2,")
            // Use FIND_IN_SET for clean matching; trim trailing commas
            $db = \Config\Database::connect();
            $table = $db->prefixTable('accounts');
            $result = $db->query("
                SELECT * FROM {$table}
                WHERE FIND_IN_SET(?, TRIM(TRAILING ',' FROM db_names)) > 0
                LIMIT 1
            ", [$dbName])->getRowArray();
            if ($result)
                return $result;
        }

        return null;
    }

    /**
     * Retrieves accounts with aggregated disk usage from records.
     */
    /**
     * Retrieves accounts with aggregated disk usage from records.
     * @param array $allowedDomains List of domains to filter by (empty = all)
     */
    public function getAccountsWithSummary(array $allowedDomains = [])
    {
        // Select accounts and aggregate size
        // We use 'left' join to include accounts with no records (size 0)
        $builder = $this->select('accounts.*, SUM(r.size_bytes) as total_size, COUNT(r.id) as record_count')
            ->join('records r', 'r.account_id = accounts.id', 'left');

        if (!empty($allowedDomains)) {
            $builder->whereIn('accounts.domain', $allowedDomains);
        }

        return $builder->groupBy('accounts.id')
            ->orderBy('total_size', 'DESC')
            ->findAll();
    }

    /**
     * Retrieves subdomain breakdown (size by kind) for an account on the latest date.
     *
     * @param string $domain The account domain
     * @return array Pivoted data: [subdomain => [account => X, mail => Y, ...]]
     */
    public function getSubdomainBreakdown(string $domain): array
    {
        $acct = $this->where('domain', $domain)->first();
        if (!$acct) {
            return [];
        }

        $db = \Config\Database::connect();
        $parentHome = $acct['home_directory'];
        $recordsT = $db->prefixTable('records');
        $accountsT = $db->prefixTable('accounts');

        $rows = $db->query("
            SELECT
                REPLACE(r.path, ?, '') as subdomain,
                r.kind,
                SUM(r.size_bytes) as size
            FROM {$recordsT} r
            WHERE r.account_id IN (
                SELECT id FROM {$accountsT} WHERE home_directory LIKE ? OR home_directory = ?
            )
            AND DATE(r.time) = (SELECT MAX(DATE(time)) FROM {$recordsT})
            AND r.path LIKE ?
            GROUP BY subdomain, r.kind
            ORDER BY size DESC
        ", [
            $parentHome . '/domains/',
            $parentHome . '/domains/%',
            $parentHome,
            $parentHome . '/%'
        ])->getResultArray();

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
        return $this->groupStart()
            ->where('parent_id', 0)
            ->orWhere('parent_id', null)
            ->groupEnd()
            ->orderBy('domain', 'ASC')
            ->findAll();
    }
}
