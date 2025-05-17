<?php
// Include database connection
require_once '../includes/database.php';

// Set headers to handle AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$data = json_decode(file_get_contents('php://input'), true);

// If form was submitted traditionally
if (empty($data)) {
    $data = $_POST;
}

// Validate input data
if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Get database instance
    $db = Database::getInstance();
    
    // Check if user already exists
    $check_user = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($check_user->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Insert new user with correct table structure
    $sql = "INSERT INTO users (first_name, last_name, email, password_hash, created_at, is_active) VALUES (?, ?, ?, ?, NOW(), 1)";
    $firstName = $name; // For simplicity, we'll put the full name in first_name
    $lastName = ''; // Leave last_name empty for now
    $stmt = $db->query($sql, [$firstName, $lastName, $email, $hashed_password]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Registration successful',
            'user_id' => $db->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
