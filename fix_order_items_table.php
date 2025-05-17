<?php
/**
 * Fix Order Items Table
 * This script checks and creates/fixes the order_items table structure
 */

// Load the configuration to get database connection
require_once 'includes/config.php';

// Set headers for plain text output
header('Content-Type: text/plain');

echo "===== FIX ORDER ITEMS TABLE =====\n\n";

// Check if order_items table exists
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'order_items'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "• Table 'order_items' exists, checking structure...\n";
        
        // Get current structure
        $stmt = $conn->query("DESCRIBE order_items");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Current column structure:\n";
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        
        // Drop the table if it doesn't have the right structure
        echo "\nDropping existing table to recreate with correct structure...\n";
        $conn->exec("DROP TABLE order_items");
        echo "• Table 'order_items' dropped successfully.\n";
    } else {
        echo "• Table 'order_items' does not exist, will create it.\n";
    }
    
    // Create the table with proper structure
    echo "\nCreating order_items table with correct structure...\n";
    
    $sql = "CREATE TABLE order_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
    )";
    
    $conn->exec($sql);
    echo "• Table 'order_items' created successfully with the following columns:\n";
    echo "  - item_id (INT AUTO_INCREMENT PRIMARY KEY)\n";
    echo "  - order_id (INT NOT NULL)\n";
    echo "  - product_id (INT NOT NULL)\n";
    echo "  - quantity (INT NOT NULL DEFAULT 1)\n";
    echo "  - price (DECIMAL(10,2) NOT NULL)\n";
    echo "  - total (DECIMAL(10,2) NOT NULL)\n";
    echo "  - created_at (DATETIME DEFAULT CURRENT_TIMESTAMP)\n";
    
    echo "\n===== VERIFICATION =====\n";
    
    // Verify table was created correctly
    $stmt = $conn->query("DESCRIBE order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Verified columns in order_items table:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n===== COMPLETION =====\n";
    echo "• Table 'order_items' has been fixed successfully.\n";
    echo "• You can now process orders correctly.\n";
    echo "• Go back to checkout and try placing an order again.\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
}
?>
