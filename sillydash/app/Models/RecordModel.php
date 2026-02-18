<?php

namespace App\Models;

use CodeIgniter\Model;

class RecordModel extends Model
{
    protected $table = 'records';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['file_id', 'account_id', 'size_bytes', 'kind', 'path', 'time'];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
}
