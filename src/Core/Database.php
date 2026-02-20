<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;
    private $prefix = '';

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $this->prefix = $config['DBPrefix'] ?? '';

        $charset = $config['charset'] ?? 'utf8mb4';
        $dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            // For setup, we might not have a valid DB connection yet.
            // If the error is 'Unknown database', we might still want to return a connection 
            // without the dbname for the setup process to create it.
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $dsnNoDb = "mysql:host={$config['hostname']};charset={$charset}";
                $this->pdo = new PDO($dsnNoDb, $config['username'], $config['password'], $options);
            } else {
                throw new \Exception($e->getMessage(), (int) $e->getCode());
            }
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data)
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $set = [];
        $values = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = ?";
            $values[] = $value;
        }

        $setClause = implode(', ', $set);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $values = array_merge($values, $whereParams);
        $this->query($sql, $values);
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    public function insertBatch($table, $data)
    {
        if (empty($data)) {
            return 0;
        }

        $keys = array_keys($data[0]);
        $fields = implode(', ', $keys);

        $placeholders = '(' . implode(', ', array_fill(0, count($keys), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($data), $placeholders));

        $sql = "INSERT INTO {$table} ({$fields}) VALUES {$allPlaceholders}";

        $values = [];
        foreach ($data as $row) {
            foreach ($keys as $key) {
                $values[] = $row[$key];
            }
        }

        $this->query($sql, $values);
        return count($data);
    }
}
