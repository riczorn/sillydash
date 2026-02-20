<?php

namespace App\Models;

use App\Core\Model;

class FileModel extends Model
{
    protected $table = 'files';
    protected $primaryKey = 'id';
    protected $allowedFields = ['filename', 'type', 'file_date', 'processed'];

    // Find unprocessed files of a specific type (excluding virtualmin)
    public function getUnprocessed($limit = 5)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE processed = 0 
                AND type != 'virtualmin' 
                ORDER BY file_date DESC 
                LIMIT " . (int) $limit;

        return $this->db->fetchAll($sql);
    }
    public function getTotalFiles()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->fetch($sql);
        return $result['count'] ?? 0;
    }

    public function getStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN processed = 1 THEN 1 ELSE 0 END) as processed,
                    SUM(CASE WHEN processed = 0 THEN 1 ELSE 0 END) as pending
                FROM {$this->table}";
        return $this->db->fetch($sql);
    }
}
