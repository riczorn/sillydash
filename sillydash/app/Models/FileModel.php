<?php

namespace App\Models;

use CodeIgniter\Model;

class FileModel extends Model
{
    protected $table = 'files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['filename', 'type', 'file_date', 'processed'];

    protected $useTimestamps = false; // Dropped created_at, updated_at
    protected $dateFormat = 'datetime';
    // protected $createdField = 'created_at';
    // protected $updatedField = 'updated_at';

    // Find unprocessed files of a specific type (excluding virtualmin)
    public function getUnprocessed($limit = 5)
    {
        return $this->where('processed', 0) // Check processed flag
            ->where('type !=', 'virtualmin')
            ->orderBy('file_date', 'DESC') // Changed from generated_at
            ->limit($limit)
            ->findAll();
    }
}
