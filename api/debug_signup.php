<?php
// Debug file for signup process

// Create a log file
function logDebug($message) {
    $log = date('Y-m-d H:i:s') . ' - ' . print_r($message, true) . "\n";
    file_put_contents('../logs/debug.log', $log, FILE_APPEND);
}

logDebug('Signup debug process started');

// Include database connection
require_once '../includes/database.php';
logDebug('Database include loaded');

// Get raw POST data
$raw_data = file_get_contents('php://input');
logDebug('Raw data received: ' . $raw_data);

// Decode JSON data
$data = json_decode($raw_data, true);
logDebug('Decoded data: ' . print_r($data, true));

// Database connection test
try {
    $db = Database::getInstance();
    logDebug('Database connection successful');
    
    // Test database query
    $test_query = $db->query("SHOW TABLES");
    $tables = [];
    while ($row = $test_query->fetch()) {
        $tables[] = $row;
    }
    logDebug('Database tables: ' . print_r($tables, true));
    
    // Check if users table exists
    $users_query = $db->query("SHOW TABLES LIKE 'users'");
    if ($users_query->rowCount() > 0) {
        logDebug('Users table exists');
        
        // Check users table structure
        $structure_query = $db->query("DESCRIBE users");
        $columns = [];
        while ($col = $structure_query->fetch()) {
            $columns[] = $col;
        }
        logDebug('Users table structure: ' . print_r($columns, true));
    } else {
        logDebug('Users table does not exist!');
    }
    
    // Process sample data
    if ($data) {
        // Just test format, don't actually insert
        $test_name = isset($data['name']) ? $data['name'] : 'TEST NAME';
        $test_email = isset($data['email']) ? $data['email'] : 'test@example.com';
        $test_password = isset($data['password']) ? $data['password'] : 'testpassword';
        
        logDebug('Test user data formatted:');
        logDebug(['first_name' => $test_name, 'email' => $test_email]);
        
        // Check SQL format
        $sql = "INSERT INTO users (first_name, last_name, email, password_hash, created_at, is_active) VALUES (?, ?, ?, ?, NOW(), 1)";
        logDebug('SQL query: ' . $sql);
    }
    
} catch (PDOException $e) {
    logDebug('Database ERROR: ' . $e->getMessage());
}

// Return debug information as JSON
header('Content-Type: application/json');
echo json_encode([
    'debug' => true,
    'message' => 'Debug information has been logged. Check the logs/debug.log file.',
    'raw_data' => $raw_data,
    'parsed_data' => $data
]);
