<?php
/**
 * PEGASUS ERP - Database Singleton
 * PDO wrapper for PostgreSQL with prepared statements
 */

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;options=\'--client_encoding=%s\'',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            error_log('PEGASUS DB Connection Error: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed. Please check configuration.');
        }
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup()
    {
        throw new RuntimeException('Cannot unserialize singleton.');
    }

    /**
     * Get the singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the raw PDO connection
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Execute a query with prepared statement parameters
     * Returns the PDOStatement
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('PEGASUS Query Error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Fetch a single row
     */
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert a row and return the new ID
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders}) RETURNING id";

        $stmt = $this->query($sql, array_values($data));
        $row = $stmt->fetch();
        return $row ? $row['id'] : null;
    }

    /**
     * Update rows matching a WHERE clause
     * $where is an associative array of column => value for the WHERE clause
     */
    public function update($table, $data, $where)
    {
        $setParts = [];
        $params = [];
        foreach ($data as $col => $val) {
            $setParts[] = "{$col} = ?";
            $params[] = $val;
        }

        $whereParts = [];
        foreach ($where as $col => $val) {
            $whereParts[] = "{$col} = ?";
            $params[] = $val;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts)
             . " WHERE " . implode(' AND ', $whereParts);

        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Soft delete a row by setting is_deleted = true
     */
    public function softDelete($table, $id, $pkCol = 'id')
    {
        $sql = "UPDATE {$table} SET is_deleted = true, updated_at = NOW() WHERE {$pkCol} = ?";
        $stmt = $this->query($sql, [$id]);
        return $stmt->rowCount();
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }
}
