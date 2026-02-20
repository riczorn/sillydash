<?php

namespace App\Core;

class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $allowedFields = [];

    public function __construct()
    {
        if (file_exists(ROOTPATH . 'config.php')) {
            $this->db = Database::getInstance();
        }
        if (!empty($this->table) && $this->db) {
            $this->table = $this->db->getPrefix() . $this->table;
        }
    }

    public function findAll()
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->fetchAll($sql);
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function insert($data)
    {
        return $this->db->insert($this->table, $this->filterData($data));
    }

    public function update($id, $data)
    {
        $where = "{$this->primaryKey} = ?";
        $this->db->update($this->table, $this->filterData($data), $where, [$id]);
        return true;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->query($sql, [$id]);
    }

    protected function filterData($data)
    {
        if (empty($this->allowedFields)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->allowedFields));
    }

    public function where($field, $value)
    {
        // Simple where clause builder - to be used cautiously
        // This is a very basic replacement for CI4's builder.
        // It returns a builder-like object or specific results?
        // For simplicity in this vanilla migration, let's implement a 'where' that returns results.
        // Or better, let's just make direct SQL calls in the specific models for complex queries.

        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->db->fetchAll($sql, [$value]);
    }

    public function first()
    {
        // This usually follows a where clause in CI4.
        // Since we don't have a chainable query builder state, we can't easily implement this 
        // without keeping state.
        // For now, let's implement a simple `first` that fetches the first record of the table.
        $sql = "SELECT * FROM {$this->table} LIMIT 1";
        return $this->db->fetch($sql);
    }

    public function insertBatch($data)
    {
        return $this->db->insertBatch($this->table, $data);
    }
}
