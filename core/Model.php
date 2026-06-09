<?php
/**
 * PEGASUS ERP - Base Model
 * All models should extend this class
 */

class Model
{
    protected $table = '';
    protected $primaryKey = 'id';
    protected $db;

    /** Enable soft delete (is_deleted flag) */
    protected $softDelete = true;

    /** Enable audit logging */
    protected $auditLog = true;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a single record by primary key
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        if ($this->softDelete) {
            $sql .= " AND is_deleted = false";
        }
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get all records with optional WHERE, ORDER, and LIMIT
     * $where: associative array of column => value
     * $order: e.g. 'created_at DESC'
     * $limit: integer or null
     */
    public function all($where = [], $order = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        $conditions = [];
        if ($this->softDelete) {
            $conditions[] = "is_deleted = false";
        }
        foreach ($where as $col => $val) {
            $conditions[] = "{$col} = ?";
            $params[] = $val;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if ($order) {
            $sql .= " ORDER BY {$order}";
        }

        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Create a new record
     * Returns the new ID
     */
    public function create($data)
    {
        // Add timestamps if not present
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // Set created_by if available and not already set
        if (!isset($data['created_by']) && Auth::check()) {
            $data['created_by'] = Auth::userId();
        }

        $id = $this->db->insert($this->table, $data);

        if ($this->auditLog && $id) {
            $this->logAudit('CREATE', $id, null, $data);
        }

        return $id;
    }

    /**
     * Update a record by primary key
     * Returns the number of affected rows
     */
    public function update($id, $data)
    {
        // Capture old data for audit
        $oldData = null;
        if ($this->auditLog) {
            $oldData = $this->find($id);
        }

        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_by']) && Auth::check()) {
            $data['updated_by'] = Auth::userId();
        }

        $result = $this->db->update($this->table, $data, [$this->primaryKey => $id]);

        if ($this->auditLog && $result > 0) {
            $this->logAudit('UPDATE', $id, $oldData, $data);
        }

        return $result;
    }

    /**
     * Soft delete a record (set is_deleted = true)
     */
    public function softDelete($id)
    {
        $data = [
            'is_deleted' => true,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (Auth::check()) {
            $data['updated_by'] = Auth::userId();
        }

        $result = $this->db->update($this->table, $data, [$this->primaryKey => $id]);

        if ($this->auditLog && $result > 0) {
            $this->logAudit('DELETE', $id, null, null);
        }

        return $result;
    }

    /**
     * Search records by keyword across specified columns
     * $keyword: search term
     * $columns: array of column names to search in
     */
    public function search($keyword, $columns)
    {
        if (empty($keyword) || empty($columns)) {
            return $this->all();
        }

        $conditions = [];
        $params = [];

        foreach ($columns as $col) {
            $conditions[] = "CAST({$col} AS TEXT) ILIKE ?";
            $params[] = '%' . $keyword . '%';
        }

        $sql = "SELECT * FROM {$this->table} WHERE (" . implode(' OR ', $conditions) . ")";

        if ($this->softDelete) {
            $sql .= " AND is_deleted = false";
        }

        $sql .= " ORDER BY {$this->primaryKey} DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Paginate results
     * Returns ['data' => [...], 'total' => int, 'page' => int, 'perPage' => int, 'totalPages' => int]
     */
    public function paginate($page = 1, $perPage = 20, $where = [], $order = null)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);
        $offset = ($page - 1) * $perPage;

        $params = [];
        $conditions = [];

        if ($this->softDelete) {
            $conditions[] = "is_deleted = false";
        }
        foreach ($where as $col => $val) {
            $conditions[] = "{$col} = ?";
            $params[] = $val;
        }

        $whereSql = '';
        if (!empty($conditions)) {
            $whereSql = " WHERE " . implode(' AND ', $conditions);
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table}" . $whereSql;
        $countRow = $this->db->fetch($countSql, $params);
        $total = (int) ($countRow['total'] ?? 0);

        // Get paginated data
        $dataSql = "SELECT * FROM {$this->table}" . $whereSql;
        if ($order) {
            $dataSql .= " ORDER BY {$order}";
        } else {
            $dataSql .= " ORDER BY {$this->primaryKey} DESC";
        }
        $dataSql .= " LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->db->fetchAll($dataSql, $params);

        return [
            'data'       => $data,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Write an audit log entry
     */
    protected function logAudit($action, $recordId, $oldData = null, $newData = null)
    {
        try {
            $logData = [
                'table_name' => $this->table,
                'record_id'  => $recordId,
                'action'     => $action,
                'old_data'   => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                'new_data'   => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                'user_id'    => Auth::userId(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $columns = implode(', ', array_keys($logData));
            $placeholders = implode(', ', array_fill(0, count($logData), '?'));
            $sql = "INSERT INTO audit_logs ({$columns}) VALUES ({$placeholders})";

            $this->db->query($sql, array_values($logData));
        } catch (Exception $e) {
            // Don't let audit logging failures break the application
            error_log('PEGASUS Audit Log Error: ' . $e->getMessage());
        }
    }
}
