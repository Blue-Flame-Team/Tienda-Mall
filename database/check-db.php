<?php
/**
 * Database Connection Checker and Setup Launcher
 * This file helps diagnose database issues and can trigger the setup process
 */

// Include necessary files
require_once '../includes/config.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to output styled messages
function outputMessage($message, $type = 'info') {
    $colors = [
        'success' => '#28a745',
        'danger' => '#dc3545',
        'warning' => '#ffc107',
        'info' => '#17a2b8'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid {$color}; background-color: " . hexToRgba($color, 0.1) . "; color: {$color}; border-radius: 4px;'>{$message}</div>";
}

// Helper function to convert hex color to rgba with opacity
function hexToRgba($hex, $opacity = 1) {
    $hex = str_replace('#', '', $hex);
    
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return "rgba($r, $g, $b, $opacity)";
}

// Function to check the database connection
function checkDatabaseConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        
        outputMessage("✅ Successfully connected to MySQL server", 'success');
        return true;
    } catch (PDOException $e) {
        outputMessage("❌ Failed to connect to MySQL server: " . $e->getMessage(), 'danger');
        return false;
    }
}

// Function to check if the database exists
function checkDatabaseExists() {
    try {
        // Connect without database
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        
        // Check if database exists
        $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            outputMessage("✅ Database '" . DB_NAME . "' exists", 'success');
            return true;
        } else {
            outputMessage("❌ Database '" . DB_NAME . "' does not exist", 'danger');
            return false;
        }
    } catch (PDOException $e) {
        outputMessage("❌ Error checking if database exists: " . $e->getMessage(), 'danger');
        return false;
    }
}

// Function to check if required tables exist
function checkTablesExist() {
    try {
        // Connect to the database
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        
        // Required tables to check
        $requiredTables = ['users', 'shipping_addresses', 'product', 'category', 'orders', 'order_items', 'wishlist'];
        $missingTables = [];
        
        // Check each table
        foreach ($requiredTables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() === 0) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            outputMessage("✅ All required tables exist", 'success');
            return true;
        } else {
            outputMessage("❌ Missing tables: " . implode(', ', $missingTables), 'danger');
            return false;
        }
    } catch (PDOException $e) {
        outputMessage("❌ Error checking tables: " . $e->getMessage(), 'danger');
        return false;
    }
}

// Main execution
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Connection Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .action-button {
            display: inline-block;
            background-color: #17a2b8;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .danger-button {
            background-color: #dc3545;
        }
        .success-button {
            background-color: #28a745;
        }
    </style>
</head>
<body>
    <h1>Database Connection Checker</h1>";

// Run the checks
$dbServerConnected = checkDatabaseConnection();
$dbExists = $dbServerConnected ? checkDatabaseExists() : false;
$tablesExist = $dbExists ? checkTablesExist() : false;

// Output summary and recommendations
echo "<h2>Summary</h2>";

if (!$dbServerConnected) {
    outputMessage("MySQL server connection failed. Please check your MySQL server is running and credentials are correct.", 'danger');
    
    echo "<h3>Troubleshooting Steps:</h3>
    <ol>
        <li>Verify MySQL server is running in WAMP/XAMPP</li>
        <li>Check username and password in <code>includes/config.php</code></li>
        <li>Make sure the port is correct (default: 3306)</li>
    </ol>";
} elseif (!$dbExists) {
    outputMessage("MySQL server is running, but the database does not exist.", 'warning');
    
    echo "<p>You need to create the database by running the setup script:</p>
    <a href='setup_database.php' class='action-button'>Run Database Setup</a>";
} elseif (!$tablesExist) {
    outputMessage("Database exists, but some required tables are missing.", 'warning');
    
    echo "<p>You need to run the database setup script to create all tables:</p>
    <a href='setup_database.php' class='action-button'>Run Database Setup</a>";
} else {
    outputMessage("All checks passed! Your database is properly configured.", 'success');
    
    echo "<p>Options:</p>
    <a href='../index.php' class='action-button success-button'>Go to Homepage</a>
    <a href='setup_database.php' class='action-button danger-button'>Reset Database (Warning: This will delete all data)</a>";
}

echo "<p><br><a href='{$_SERVER['PHP_SELF']}'>Run checks again</a></p>";
echo "</body></html>";
