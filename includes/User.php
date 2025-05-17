<?php
/**
 * User Class
 * Handles user-related operations
 */

// Ya no necesitamos incluir functions.php aquí porque se incluye en init.php
require_once 'db_bridge.php';

class User {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Register a new user
     * @param array $userData User data (email, password, first_name, last_name, etc.)
     * @return bool|int User ID if successful, false otherwise
     */
    public function register($userData) {
        // Validate input
        if (empty($userData['email']) || empty($userData['password']) || 
            empty($userData['first_name']) || empty($userData['last_name'])) {
            return false;
        }
        
        // Check if email already exists
        $stmt = $this->db->query("SELECT email FROM users WHERE email = ?", [$userData['email']]);
        if ($stmt->rowCount() > 0) {
            return false; // Email already exists
        }
        
        // Hash password
        $passwordHash = hashPassword($userData['password']);
        
        // Generate verification token
        $verificationToken = generateToken();
        
        // Insert user into database
        $sql = "INSERT INTO users (email, password_hash, first_name, last_name, phone, verification_token, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $userData['email'],
            $passwordHash,
            $userData['first_name'],
            $userData['last_name'],
            $userData['phone'] ?? null,
            $verificationToken
        ];
        
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt->rowCount() > 0) {
            // Get the last inserted ID
            $lastId = $this->db->getConnection()->lastInsertId();
            
            // Here you would typically send a verification email
            // This is a placeholder for that functionality
            
            return $lastId;
        }
        
        return false;
    }
    
    /**
     * Login a user
     * @param string $email User email
     * @param string $password User password
     * @return bool|array User data if successful, false otherwise
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return false;
        }
        
        $sql = "SELECT user_id, email, password_hash, first_name, last_name, is_active, email_verified 
                FROM users 
                WHERE email = ?";
        
        $stmt = $this->db->query($sql, [$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify password
            if (verifyPassword($password, $user['password_hash'])) {
                // Check if user is active
                if ($user['is_active'] == 0) {
                    return 'inactive'; // Account is deactivated
                }
                
                // Update last login time
                $this->db->query("UPDATE users SET last_login = NOW() WHERE user_id = ?", [$user['user_id']]);
                
                // Remove password hash from data to be returned
                unset($user['password_hash']);
                
                return $user;
            }
        }
        
        return false;
    }
    
    /**
     * Get user by ID
     * @param int $userId User ID
     * @return array|bool User data if found, false otherwise
     */
    public function getUserById($userId) {
        $sql = "SELECT user_id, email, first_name, last_name, phone, date_of_birth, profile_image, is_active, created_at, last_login, email_verified 
                FROM users 
                WHERE user_id = ?";
        
        $stmt = $this->db->query($sql, [$userId]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    /**
     * Update user profile
     * @param int $userId User ID
     * @param array $userData User data to update
     * @return bool True if successful, false otherwise
     */
    public function updateProfile($userId, $userData) {
        // Build SQL query dynamically based on provided data
        $sql = "UPDATE users SET ";
        $params = [];
        
        foreach ($userData as $key => $value) {
            // Skip user_id and fields that should not be updated this way
            if (in_array($key, ['user_id', 'email', 'password_hash', 'is_active', 'email_verified', 'verification_token', 'reset_token', 'reset_token_expiry'])) {
                continue;
            }
            
            $sql .= "$key = ?, ";
            $params[] = $value;
        }
        
        $sql .= "updated_at = NOW() WHERE user_id = ?";
        $params[] = $userId;
        
        $stmt = $this->db->query($sql, $params);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Change user password
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool True if successful, false otherwise
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current password hash
        $sql = "SELECT password_hash FROM users WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$userId]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify current password
            if (verifyPassword($currentPassword, $user['password_hash'])) {
                // Hash new password
                $newPasswordHash = hashPassword($newPassword);
                
                // Update password
                $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                $stmt = $this->db->query($sql, [$newPasswordHash, $userId]);
                
                return $stmt->rowCount() > 0;
            }
        }
        
        return false;
    }
    
    /**
     * Request password reset
     * @param string $email User email
     * @return bool True if successful, false otherwise
     */
    public function requestPasswordReset($email) {
        // Check if email exists
        $sql = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $this->db->query($sql, [$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Generate reset token
            $resetToken = generateToken();
            $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Update user with reset token
            $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
            $stmt = $this->db->query($sql, [$resetToken, $resetTokenExpiry, $user['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                // Here you would typically send a reset email
                // This is a placeholder for that functionality
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Reset password with token
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool True if successful, false otherwise
     */
    public function resetPassword($token, $newPassword) {
        // Check if token exists and is not expired
        $sql = "SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $this->db->query($sql, [$token]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Hash new password
            $newPasswordHash = hashPassword($newPassword);
            
            // Update password and clear token
            $sql = "UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?";
            $stmt = $this->db->query($sql, [$newPasswordHash, $user['user_id']]);
            
            return $stmt->rowCount() > 0;
        }
        
        return false;
    }
    
    /**
     * Verify email with token
     * @param string $token Verification token
     * @return bool True if successful, false otherwise
     */
    public function verifyEmail($token) {
        // Check if token exists
        $sql = "SELECT user_id FROM users WHERE verification_token = ?";
        $stmt = $this->db->query($sql, [$token]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Mark email as verified and clear token
            $sql = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE user_id = ?";
            $stmt = $this->db->query($sql, [$user['user_id']]);
            
            return $stmt->rowCount() > 0;
        }
        
        return false;
    }
}
?>
