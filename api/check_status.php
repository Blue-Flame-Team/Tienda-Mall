<?php
// PHP and Database Connection Status Checker

echo "<h2>PHP and Database Connection Status Checker</h2>";

// Check PHP version
echo "<h3>1. PHP Status</h3>";
echo "PHP Version: " . phpversion() . "<br>";

// Check if important PHP extensions are loaded
$required_extensions = ['mysqli', 'pdo', 'pdo_mysql', 'json', 'session'];
echo "<h4>Required PHP Extensions:</h4>";
echo "<ul>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li>✅ {$ext} is loaded</li>";
    } else {
        echo "<li>❌ {$ext} is NOT loaded</li>";
    }
}
echo "</ul>";

// Check if the includes directory is accessible
echo "<h3>2. File Structure Check</h3>";
if (file_exists('../includes/database.php')) {
    echo "✅ database.php file found<br>";
    include_once '../includes/database.php';
} else {
    echo "❌ database.php file not found<br>";
    die("Cannot continue without database.php file");
}

// Check database connection
echo "<h3>3. Database Connection</h3>";
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "✅ Successfully connected to database<br>";
    
    // Check if the database has the required tables
    echo "<h3>4. Database Tables Check</h3>";
    $tables = ['users'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result && $result->rowCount() > 0) {
            echo "✅ Table '{$table}' exists<br>";
            
            // Show table structure
            echo "<h4>Structure of '{$table}' table:</h4>";
            $columns = $conn->query("DESCRIBE {$table}");
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                foreach ($column as $key => $value) {
                    echo "<td>{$value}</td>";
                }
                echo "</tr>";
            }
            echo "</table><br>";
            
            // Count records
            $count = $conn->query("SELECT COUNT(*) as count FROM {$table}")->fetch(PDO::FETCH_ASSOC);
            echo "Records in '{$table}': {$count['count']}<br>";
        } else {
            echo "❌ Table '{$table}' does not exist<br>";
            echo "<a href='db_setup.php' style='display:inline-block; margin:10px 0; padding:5px 10px; background:#007BFF; color:#fff; text-decoration:none; border-radius:3px;'>Create Tables Now</a><br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<h4>Troubleshooting tips:</h4>";
    echo "<ol>";
    echo "<li>Ensure WAMP/MySQL service is running</li>";
    echo "<li>Check database credentials in includes/database.php</li>";
    echo "<li>Make sure database 'tienda_mall' exists</li>";
    echo "</ol>";
    
    // Check if database exists
    try {
        $rootConn = new PDO("mysql:host=localhost", "root", "");
        $result = $rootConn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'tienda_mall'");
        if ($result && $result->rowCount() > 0) {
            echo "✅ Database 'tienda_mall' exists<br>";
        } else {
            echo "❌ Database 'tienda_mall' does not exist<br>";
            echo "<p>You need to create the database first:</p>";
            echo "<pre>CREATE DATABASE tienda_mall;</pre>";
        }
    } catch (PDOException $e2) {
        echo "❌ Could not connect to MySQL server: " . $e2->getMessage() . "<br>";
    }
}

// Check if necessary API files exist
echo "<h3>5. API Files Check</h3>";
$api_files = ['login.php', 'signup.php', 'db_setup.php'];
foreach ($api_files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} exists<br>";
    } else {
        echo "❌ {$file} does not exist<br>";
    }
}

// Check if necessary page files exist
echo "<h3>6. Page Files Check</h3>";
$page_files = ['../pages/login.php', '../pages/signup.php'];
foreach ($page_files as $file) {
    if (file_exists($file)) {
        echo "✅ " . basename($file) . " exists<br>";
    } else {
        echo "❌ " . basename($file) . " does not exist<br>";
    }
}

echo "<br><p>Status check completed at: " . date('Y-m-d H:i:s') . "</p>";
?>
