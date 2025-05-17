<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Create connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if contact_messages table exists
    $result = $conn->query("SHOW TABLES LIKE 'contact_messages'");
    $tableExists = $result->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the contact_messages table
        $sql = "CREATE TABLE contact_messages (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $conn->exec($sql);
        echo "<p style='color:green;'>تم إنشاء جدول contact_messages بنجاح!</p>";
    } else {
        echo "<p style='color:blue;'>جدول contact_messages موجود بالفعل.</p>";
    }
    
    // Display table structure
    $stmt = $conn->query("DESCRIBE contact_messages");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>هيكل جدول contact_messages:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Add a link to return to contact page
    echo "<p><a href='pages/contact.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background-color:#DB4444; color:white; text-decoration:none; border-radius:5px;'>العودة إلى صفحة الاتصال</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>
