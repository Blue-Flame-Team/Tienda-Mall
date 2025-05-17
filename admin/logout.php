<?php
/**
 * Tienda Mall E-commerce Platform
 * Admin Logout
 */

// Start session if not already started
session_start();

// Unset all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);

// Redirect to login page
header('Location: login.php');
exit;
?>
