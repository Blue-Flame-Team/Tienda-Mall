<?php
/**
 * Enhanced Database Connection Class
 * Provides seamless database operations for Tienda Mall e-commerce platform
 */

class Database {
    private $conn;
    private static $instance;
    private $queryCount = 0;
    private $lastQueryTime = 0;
    private $totalQueryTime = 0;

    /**
     * Private constructor - singleton pattern
     */
    private function __construct() {
        try {
            // Create a PDO instance
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            $this->logError('Connection Error', $e->getMessage());
            
            if (defined('DEV_MODE') && DEV_MODE === true) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }

    /**
     * Get database instance - singleton pattern
     * @return Database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     * @return PDO connection
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Execute a query with parameters
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        $this->queryCount++;
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $this->lastQueryTime = microtime(true) - $startTime;
            $this->totalQueryTime += $this->lastQueryTime;
            
            return $stmt;
        } catch (PDOException $e) {
            // Guardar error detallado en los logs
            $this->logError('Query Error', $e->getMessage(), $sql, $params);
            
            // Extraer información más específica del error
            $errorInfo = $e->errorInfo ?? [];
            $errorCode = isset($errorInfo[1]) ? $errorInfo[1] : $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Mensajes de error más detallados según el código
            $userFriendlyMessage = $this->getUserFriendlyErrorMessage($errorCode, $errorMessage);
            
            if (defined('DEV_MODE') && DEV_MODE === true) {
                echo '<div style="border:2px solid red; padding:10px; margin:10px; background:#ffeeee;">';
                echo '<h3>Database Query Error:</h3>';
                echo '<p>' . $e->getMessage() . '</p>';
                echo '<p><strong>Error Code:</strong> ' . $errorCode . '</p>';
                echo '<p><strong>SQL:</strong> ' . $sql . '</p>';
                echo '<p><strong>Parameters:</strong> ' . print_r($params, true) . '</p>';
                echo '<p><strong>User Friendly Message:</strong> ' . $userFriendlyMessage . '</p>';
                echo '</div>';
                exit;
            } else {
                throw new Exception($userFriendlyMessage);
            }
        }
    }
    
    /**
     * Execute a SELECT query and fetch all results
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @param int $fetchMode PDO fetch mode
     * @return array Results of the query
     */
    public function select($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll($fetchMode);
    }
    
    /**
     * Execute a SELECT query and fetch a single row
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @param int $fetchMode PDO fetch mode
     * @return array|bool Single row or false if no results
     */
    public function selectOne($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch($fetchMode);
    }
    
    /**
     * Execute an INSERT query
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @return int Last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql, array_values($data));
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Execute an UPDATE query
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value pairs
     * @param string $where WHERE clause
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClauses = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setClauses[] = "$column = ?";
            $params[] = $value;
        }
        
        $setClause = implode(', ', $setClauses);
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        $stmt = $this->query($sql, array_merge($params, $whereParams));
        return $stmt->rowCount();
    }
    
    /**
     * Execute a DELETE query
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback() {
        $this->conn->rollBack();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string Last inserted ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Get query statistics
     * 
     * @return array Query statistics
     */
    public function getQueryStats() {
        return [
            'count' => $this->queryCount,
            'last_time' => $this->lastQueryTime,
            'total_time' => $this->totalQueryTime,
            'avg_time' => $this->queryCount > 0 ? $this->totalQueryTime / $this->queryCount : 0
        ];
    }
    
    /**
     * Log an error
     * 
     * @param string $type Error type
     * @param string $message Error message
     * @param string $sql SQL query (optional)
     * @param array $params Query parameters (optional)
     */
    private function logError($type, $message, $sql = null, $params = null) {
        // Asegurarse de que existe el directorio de logs
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Crear un mensaje de error más detallado
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message\n";
        
        if ($sql !== null) {
            $logMessage .= "SQL Query: $sql\n";
        }
        
        if ($params !== null) {
            $logMessage .= "Parameters: " . print_r($params, true) . "\n";
        }
        
        // Incluir backtrace para ver la secuencia de llamadas
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $logMessage .= "Trace: \n";
        foreach ($trace as $i => $step) {
            $logMessage .= "  #$i {$step['file']}({$step['line']}): ";
            if (isset($step['class'])) {
                $logMessage .= "{$step['class']}{$step['type']}";
            }
            $logMessage .= "{$step['function']}()\n";
        }
        
        // Guardar en el archivo de log
        $logFile = $logDir . '/database_errors.log';
        file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
        
        // También registrar en el log del sistema
        error_log("[$type] $message");
    }
    
    /**
     * Get a user-friendly error message based on the error code and message
     * 
     * @param int $errorCode Error code from MySQL
     * @param string $errorMessage Original error message
     * @return string User-friendly error message
     */
    private function getUserFriendlyErrorMessage($errorCode, $errorMessage) {
        // Detectar problemas comunes basándonos en códigos o mensajes de error
        switch ($errorCode) {
            case 1146: // Table doesn't exist
                if (preg_match('/Table \'.*\.(.*?)\' doesn\'t exist/', $errorMessage, $matches)) {
                    return "La tabla '{$matches[1]}' no existe en la base de datos. Verifique el nombre de la tabla o ejecute el script de creación de tablas.";
                }
                return "Una de las tablas no existe en la base de datos. Verifique el nombre de la tabla.";
                
            case 1054: // Unknown column
                if (preg_match('/Unknown column \'(.*?)\'/', $errorMessage, $matches)) {
                    return "La columna '{$matches[1]}' no existe en la tabla. Verifique el nombre de la columna o actualice la estructura de la tabla.";
                }
                return "Una de las columnas no existe en la tabla. Verifique los nombres de las columnas.";
                
            case 1064: // Syntax error
                return "Error de sintaxis en la consulta SQL. Verifique la sintaxis de la consulta.";
                
            case 1062: // Duplicate entry
                return "Entrada duplicada. Ya existe un registro con esos datos.";
                
            case 1045: // Access denied
                return "Acceso denegado a la base de datos. Verifique el nombre de usuario y contraseña.";
                
            case 1049: // Unknown database
                return "La base de datos no existe. Verifique el nombre de la base de datos.";
                
            case 2002: // Connection refused
            case 2003: // Can't connect
                return "No se pudo conectar al servidor de base de datos. Verifique que el servidor esté en funcionamiento.";
                
            case 1213: // Deadlock
                return "Se produjo un bloqueo en la base de datos. Inténtelo de nuevo.";
                
            default:
                // Detectar problemas comunes basándonos en fragmentos del mensaje
                if (stripos($errorMessage, 'no such table') !== false) {
                    return "Una tabla mencionada en la consulta no existe. Verifique los nombres de las tablas.";
                }
                
                if (stripos($errorMessage, 'no such column') !== false) {
                    return "Una columna mencionada en la consulta no existe. Verifique los nombres de las columnas.";
                }
                
                if (stripos($errorMessage, 'constraint') !== false) {
                    return "La operación viola una restricción en la base de datos.";
                }
                
                // Mensaje genérico para otros errores
                return "Error en la base de datos (Código: $errorCode): Por favor, use la herramienta de depuración para más detalles.";
        }
    }
}
?>
