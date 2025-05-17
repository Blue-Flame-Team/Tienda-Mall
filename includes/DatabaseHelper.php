<?php
/**
 * DatabaseHelper Class
 * Provides utility methods for common database operations
 */

require_once 'Database.php';

class DatabaseHelper {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Execute a parameterized query and return all results
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for the query
     * @return array Results
     */
    public function fetchAll($sql, $params = []) {
        return $this->db->select($sql, $params);
    }
    
    /**
     * Execute a parameterized query and return a single result
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for the query
     * @return array|null Single result row or null if not found
     */
    public function fetchOne($sql, $params = []) {
        return $this->db->selectOne($sql, $params);
    }
    
    /**
     * Execute a parameterized query and return a single value
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for the query
     * @param mixed $default Default value if query returns no results
     * @return mixed Value or default
     */
    public function fetchValue($sql, $params = [], $default = null) {
        $result = $this->db->selectOne($sql, $params);
        
        if ($result && count($result) > 0) {
            return reset($result);
        }
        
        return $default;
    }
    
    /**
     * Execute a parameterized query and return the number of affected rows
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for the query
     * @return int Number of affected rows
     */
    public function execute($sql, $params = []) {
        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Insert data into a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|bool Last inserted ID or false on failure
     */
    public function insert($table, $data) {
        return $this->db->insert($table, $data);
    }
    
    /**
     * Update data in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause with placeholders
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        return $this->db->update($table, $data, $where, $whereParams);
    }
    
    /**
     * Delete data from a table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause with placeholders
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        return $this->db->delete($table, $where, $params);
    }
    
    /**
     * Begin a database transaction
     */
    public function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    /**
     * Commit a database transaction
     */
    public function commit() {
        $this->db->commit();
    }
    
    /**
     * Rollback a database transaction
     */
    public function rollback() {
        $this->db->rollback();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string The last inserted ID
     */
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }
    
    /**
     * Count rows in a table with optional WHERE clause
     * 
     * @param string $table Table name
     * @param string $where Optional WHERE clause
     * @param array $params Optional parameters for WHERE clause
     * @return int Number of rows
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table";
        
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        
        $result = $this->db->selectOne($sql, $params);
        return (int)$result['count'];
    }
    
    /**
     * Check if a record exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return bool Whether record exists
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Get pagination data for a query
     * 
     * @param string $table Table name
     * @param array $options Pagination options
     * @return array Data with pagination metadata
     */
    public function paginate($table, $options = []) {
        // Default options
        $defaults = [
            'page' => 1,
            'per_page' => 10,
            'where' => '',
            'params' => [],
            'order_by' => '',
            'columns' => '*'
        ];
        
        $options = array_merge($defaults, $options);
        extract($options);
        
        $offset = ($page - 1) * $per_page;
        
        // Build query
        $sql = "SELECT $columns FROM $table";
        
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        
        if (!empty($order_by)) {
            $sql .= " ORDER BY $order_by";
        }
        
        $sql .= " LIMIT $per_page OFFSET $offset";
        
        // Get results
        $results = $this->fetchAll($sql, $params);
        
        // Get total count for pagination
        $totalCount = $this->count($table, $where, $params);
        $totalPages = ceil($totalCount / $per_page);
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$per_page,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];
    }
}
?>
