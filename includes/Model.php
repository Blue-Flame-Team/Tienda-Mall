<?php
/**
 * Base Model Class
 * Provides common functionality for all entity models
 */

require_once 'Database.php';

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = ['password_hash'];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find a record by ID
     * 
     * @param int $id Record ID
     * @return array|null Record data or null if not found
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->selectOne($sql, [$id]);
    }
    
    /**
     * Get all records
     * 
     * @param string $orderBy Order by clause
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Records
     */
    public function all($orderBy = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        return $this->db->select($sql);
    }
    
    /**
     * Find records by a field value
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $operator Operator (default: =)
     * @return array Records
     */
    public function findBy($field, $value, $operator = '=') {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} {$operator} ?";
        return $this->db->select($sql, [$value]);
    }
    
    /**
     * Find a single record by a field value
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $operator Operator (default: =)
     * @return array|null Record data or null if not found
     */
    public function findOneBy($field, $value, $operator = '=') {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} {$operator} ?";
        return $this->db->selectOne($sql, [$value]);
    }
    
    /**
     * Create a new record
     * 
     * @param array $data Record data
     * @return int|bool New record ID or false on failure
     */
    public function create(array $data) {
        // Filter data to only include fillable fields
        $filteredData = $this->filterData($data);
        
        if (empty($filteredData)) {
            return false;
        }
        
        return $this->db->insert($this->table, $filteredData);
    }
    
    /**
     * Update a record
     * 
     * @param int $id Record ID
     * @param array $data Record data
     * @return bool Success status
     */
    public function update($id, array $data) {
        // Filter data to only include fillable fields
        $filteredData = $this->filterData($data);
        
        if (empty($filteredData)) {
            return false;
        }
        
        return $this->db->update(
            $this->table, 
            $filteredData, 
            "{$this->primaryKey} = ?", 
            [$id]
        ) > 0;
    }
    
    /**
     * Delete a record
     * 
     * @param int $id Record ID
     * @return bool Success status
     */
    public function delete($id) {
        return $this->db->delete(
            $this->table, 
            "{$this->primaryKey} = ?", 
            [$id]
        ) > 0;
    }
    
    /**
     * Execute a custom query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @param bool $single Return a single record
     * @return array|mixed Query results
     */
    public function query($sql, $params = [], $single = false) {
        if ($single) {
            return $this->db->selectOne($sql, $params);
        }
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Count records
     * 
     * @param string $where Where clause
     * @param array $params Query parameters
     * @return int Record count
     */
    public function count($where = null, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->selectOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Filter data to only include fillable fields
     * 
     * @param array $data Data to filter
     * @return array Filtered data
     */
    protected function filterData(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Remove hidden fields from data before returning to client
     * 
     * @param array $data Data to filter
     * @return array Filtered data
     */
    protected function filterHidden($data) {
        if (empty($data) || empty($this->hidden)) {
            return $data;
        }
        
        if (isset($data[0]) && is_array($data[0])) {
            // Multiple records
            foreach ($data as &$record) {
                foreach ($this->hidden as $field) {
                    unset($record[$field]);
                }
            }
        } else {
            // Single record
            foreach ($this->hidden as $field) {
                unset($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Paginate results
     * 
     * @param int $page Page number
     * @param int $perPage Records per page
     * @param string $where Where clause
     * @param array $params Query parameters
     * @param string $orderBy Order by clause
     * @return array Paginated results
     */
    public function paginate($page = 1, $perPage = 10, $where = null, $params = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $records = $this->db->select($sql, $params);
        $totalCount = $this->count($where, $params);
        
        $totalPages = ceil($totalCount / $perPage);
        
        return [
            'records' => $this->filterHidden($records),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalCount,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];
    }
}
?>
