<?php
/**
 * Admin Class
 * Handles admin-related operations
 */

require_once 'db.php';
require_once 'functions.php';

class Admin {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Admin login
     * @param string $email Admin email
     * @param string $password Admin password
     * @return bool|array Admin data if successful, false otherwise
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return false;
        }
        
        $sql = "SELECT admin_id, email, password_hash, first_name, last_name, role, is_active
                FROM admins 
                WHERE email = ?";
        
        $stmt = $this->db->query($sql, [$email]);
        
        if ($stmt->rowCount() > 0) {
            $admin = $stmt->fetch();
            
            // Verify password
            if (verifyPassword($password, $admin['password_hash'])) {
                // Check if admin is active
                if ($admin['is_active'] == 0) {
                    return 'inactive'; // Account is deactivated
                }
                
                // Update last login time
                $this->db->query("UPDATE admins SET last_login = NOW() WHERE admin_id = ?", [$admin['admin_id']]);
                
                // Log the login
                $this->logActivity($admin['admin_id'], 'login', 'Admin logged in');
                
                // Remove password hash from data to be returned
                unset($admin['password_hash']);
                
                return $admin;
            }
        }
        
        return false;
    }
    
    /**
     * Log admin activity
     * @param int $adminId Admin ID
     * @param string $action Action performed
     * @param string $description Description of the action
     * @return bool True if successful, false otherwise
     */
    public function logActivity($adminId, $action, $description) {
        $sql = "INSERT INTO admin_logs (admin_id, action, description, ip_address, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $params = [
            $adminId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $stmt = $this->db->query($sql, $params);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get admin by ID
     * @param int $adminId Admin ID
     * @return array|bool Admin data if found, false otherwise
     */
    public function getAdminById($adminId) {
        $sql = "SELECT admin_id, email, first_name, last_name, role, is_active, created_at, last_login, profile_image 
                FROM admins 
                WHERE admin_id = ?";
        
        $stmt = $this->db->query($sql, [$adminId]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    /**
     * Add new admin
     * @param array $adminData Admin data
     * @return bool|int Admin ID if successful, false otherwise
     */
    public function addAdmin($adminData) {
        // Validate input
        if (empty($adminData['email']) || empty($adminData['password']) || 
            empty($adminData['first_name']) || empty($adminData['last_name']) || 
            empty($adminData['role'])) {
            return false;
        }
        
        // Check if email already exists
        $stmt = $this->db->query("SELECT email FROM admins WHERE email = ?", [$adminData['email']]);
        if ($stmt->rowCount() > 0) {
            return false; // Email already exists
        }
        
        // Hash password
        $passwordHash = hashPassword($adminData['password']);
        
        // Insert admin into database
        $sql = "INSERT INTO admins (email, password_hash, first_name, last_name, role, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $adminData['email'],
            $passwordHash,
            $adminData['first_name'],
            $adminData['last_name'],
            $adminData['role'],
            $adminData['is_active'] ?? 1
        ];
        
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt->rowCount() > 0) {
            // Get the last inserted ID
            $lastId = $this->db->getConnection()->lastInsertId();
            
            // Log the action
            if (isset($_SESSION['admin_id'])) {
                $this->logActivity($_SESSION['admin_id'], 'create', "Created new admin: {$adminData['email']}");
            }
            
            return $lastId;
        }
        
        return false;
    }
    
    /**
     * Update admin
     * @param int $adminId Admin ID
     * @param array $adminData Admin data
     * @return bool True if successful, false otherwise
     */
    public function updateAdmin($adminId, $adminData) {
        // Build SQL query dynamically based on provided data
        $sql = "UPDATE admins SET ";
        $params = [];
        
        foreach ($adminData as $key => $value) {
            // Skip admin_id and password_hash
            if (in_array($key, ['admin_id', 'password_hash'])) {
                continue;
            }
            
            $sql .= "$key = ?, ";
            $params[] = $value;
        }
        
        $sql = rtrim($sql, ', ') . " WHERE admin_id = ?";
        $params[] = $adminId;
        
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt->rowCount() > 0) {
            // Log the action
            if (isset($_SESSION['admin_id'])) {
                $this->logActivity($_SESSION['admin_id'], 'update', "Updated admin ID: $adminId");
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete admin
     * @param int $adminId Admin ID
     * @return bool True if successful, false otherwise
     */
    public function deleteAdmin($adminId) {
        // Check if it's the last admin
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM admins WHERE role = 'super_admin'");
        $result = $stmt->fetch();
        
        // Get role of admin to be deleted
        $stmt = $this->db->query("SELECT role FROM admins WHERE admin_id = ?", [$adminId]);
        $admin = $stmt->fetch();
        
        // Don't allow deletion of the last super admin
        if ($admin && $admin['role'] == 'super_admin' && $result['count'] <= 1) {
            return false;
        }
        
        $sql = "DELETE FROM admins WHERE admin_id = ?";
        $stmt = $this->db->query($sql, [$adminId]);
        
        if ($stmt->rowCount() > 0) {
            // Log the action
            if (isset($_SESSION['admin_id'])) {
                $this->logActivity($_SESSION['admin_id'], 'delete', "Deleted admin ID: $adminId");
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Change admin password
     * @param int $adminId Admin ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return bool True if successful, false otherwise
     */
    public function changePassword($adminId, $currentPassword, $newPassword) {
        // Get current password hash
        $sql = "SELECT password_hash FROM admins WHERE admin_id = ?";
        $stmt = $this->db->query($sql, [$adminId]);
        
        if ($stmt->rowCount() > 0) {
            $admin = $stmt->fetch();
            
            // Verify current password
            if (verifyPassword($currentPassword, $admin['password_hash'])) {
                // Hash new password
                $newPasswordHash = hashPassword($newPassword);
                
                // Update password
                $sql = "UPDATE admins SET password_hash = ? WHERE admin_id = ?";
                $stmt = $this->db->query($sql, [$newPasswordHash, $adminId]);
                
                if ($stmt->rowCount() > 0) {
                    // Log the action
                    $this->logActivity($adminId, 'update', "Changed password");
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get admin activity logs
     * @param int $adminId Admin ID (optional, gets all logs if not specified)
     * @param int $limit Number of logs to get
     * @param int $offset Offset for pagination
     * @return array Logs
     */
    public function getActivityLogs($adminId = null, $limit = 50, $offset = 0) {
        $sql = "SELECT l.*, a.email, a.first_name, a.last_name 
                FROM admin_logs l
                JOIN admins a ON l.admin_id = a.admin_id ";
        
        $params = [];
        
        if ($adminId) {
            $sql .= "WHERE l.admin_id = ? ";
            $params[] = $adminId;
        }
        
        $sql .= "ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->query($sql, $params);
        
        return $stmt->fetchAll();
    }
}
?>
