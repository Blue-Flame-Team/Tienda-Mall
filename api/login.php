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
if (empty($data['email']) || empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

try {
    // Get database instance
    $db = Database::getInstance();
    
    // Check if user exists with correct email field
    $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Verify password - the column is named password_hash in the existing table
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct
            // Create session data without the password
            unset($user['password_hash']);
            
            // Start the session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set session variables
            $_SESSION['user'] = $user;
            
            // Update last_login time
            $db->query("UPDATE users SET last_login = NOW() WHERE user_id = ?", [$user['user_id']]);
            
            // Generate a display name from first_name and last_name
            $displayName = trim($user['first_name'] . ' ' . $user['last_name']);
            if (empty($displayName)) {
                $displayName = 'User'; // Fallback if no name is set
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['user_id'],
                    'name' => $displayName,
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
