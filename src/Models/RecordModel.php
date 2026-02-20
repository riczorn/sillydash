<?php

namespace App\Models;

use App\Core\Model;

class RecordModel extends Model
{
    protected $table = 'records';
    protected $primaryKey = 'id';
    protected $allowedFields = ['file_id', 'account_id', 'size_bytes', 'kind', 'path', 'time'];

    public function getRecent($limit = 5)
    {
        // Join with accounts to show domain? Or just raw records?
        // Let's just fetch raw records for now, maybe with file info if needed.
        // Assuming 'time' or 'created_at' exists. The migration showed 'time' in allowedFields.

        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT " . (int) $limit;
        return $this->db->fetchAll($sql);
    }

    /**
     * Get paths that are of kind 'account' but have no account_id mapped.
     */
    public function getUnmatchedPaths()
    {
        $sql = "SELECT path, SUM(size_bytes) as total_size, COUNT(*) as occurrences
                FROM {$this->table}
                WHERE account_id IS NULL AND kind = 'account'
                GROUP BY path
                ORDER BY total_size DESC";
        return $this->db->fetchAll($sql);
    }
}
