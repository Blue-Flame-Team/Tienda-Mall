<?php
/**
 * Admin Authentication Check
 * Verifies if user is logged in as admin before allowing access to admin pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    // Not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Function to check if admin has specific role/permission
function adminHasPermission($requiredRole) {
    $adminRole = $_SESSION['admin_role'] ?? '';
    
    // Super admin has all permissions
    if ($adminRole === 'super_admin') {
        return true;
    }
    
    // Check if admin has the required role
    if ($adminRole === $requiredRole) {
        return true;
    }
    
    // Role-based permissions
    $permissions = [
        'admin' => ['products', 'categories', 'orders', 'customers'],
        'editor' => ['products', 'categories'],
        'order_manager' => ['orders'],
    ];
    
    // Check if admin role has permission for the required action
    if (isset($permissions[$adminRole]) && in_array($requiredRole, $permissions[$adminRole])) {
        return true;
    }
    
    return false;
}
