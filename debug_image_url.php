<?php
// u0645u0644u0641 u062au0634u062eu064au0635 u0644u0645u0634u0643u0644u0629 image_url

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1 style='direction:rtl; font-family: Arial, sans-serif;'>u062au0634u062eu064au0635 u0645u0634u0643u0644u0629 image_url</h1>";

try {
    // u0627u0644u0627u062au0635u0627u0644 u0628u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='direction:rtl; text-align:right; font-family: Arial, sans-serif;'>";
    
    // u0627u0644u062au062du0642u0642 u0645u0646 u0648u062cu0648u062f u0639u0645u0648u062f image_url u0641u064a u062cu062fu0648u0644 products
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>u2714 u0639u0645u0648u062f image_url u0645u0648u062cu0648u062f u0641u064a u062cu062fu0648u0644 products</p>";
    } else {
        echo "<p style='color:red;'>u2716 u0639u0645u0648u062f image_url u063au064au0631 u0645u0648u062cu0648u062f u0641u064a u062cu062fu0648u0644 products</p>";
        
        // u0625u0636u0627u0641u0629 u0627u0644u0639u0645u0648u062f
        $conn->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
        echo "<p style='color:green;'>u2714 u062au0645 u0625u0636u0627u0641u0629 u0639u0645u0648u062f image_url u0625u0644u0649 u062cu062fu0648u0644 products</p>";
    }
    
    // u0627u0644u062au062du0642u0642 u0645u0646 u0648u062cu0648u062f u062cu062fu0648u0644 product_images
    $stmt = $conn->query("SHOW TABLES LIKE 'product_images'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>u2714 u062cu062fu0648u0644 product_images u0645u0648u062cu0648u062f</p>";
        
        // u0627u0644u062au062du0642u0642 u0645u0646 u0628u0646u064au0629 u062cu062fu0648u0644 product_images
        $stmt = $conn->query("SHOW COLUMNS FROM product_images");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>u0623u0639u0645u062fu0629 u062cu062fu0648u0644 product_images: " . implode(", ", $columns) . "</p>";
        
        // u0627u0644u062au062du0642u0642 u0645u0646 u0648u062cu0648u062f u0639u0645u0648u062f is_main
        if (in_array('is_main', $columns)) {
            echo "<p style='color:green;'>u2714 u0639u0645u0648u062f is_main u0645u0648u062cu0648u062f u0641u064a u062cu062fu0648u0644 product_images</p>";
        } else {
            echo "<p style='color:orange;'>u26a0 u0639u0645u0648u062f is_main u063au064au0631 u0645u0648u062cu0648u062f u0641u064a u062cu062fu0648u0644 product_images</p>";
            
            // u0627u0644u062au062du0642u0642 u0645u0646 u0648u062cu0648u062f u0639u0645u0648u062f is_primary u0628u062fu0644u0627u064b u0645u0646u0647
            if (in_array('is_primary', $columns)) {
                echo "<p style='color:green;'>u2714 u0639u0645u0648u062f is_primary u0645u0648u062cu0648u062f u0641u064a u062cu062fu0648u0644 product_images</p>";
            } else {
                // u0625u0636u0627u0641u0629 u0639u0645u0648u062f is_main
                $conn->exec("ALTER TABLE product_images ADD COLUMN is_main TINYINT(1) DEFAULT 0");
                echo "<p style='color:green;'>u2714 u062au0645 u0625u0636u0627u0641u0629 u0639u0645u0648u062f is_main u0625u0644u0649 u062cu062fu0648u0644 product_images</p>";
            }
        }
    } else {
        echo "<p style='color:orange;'>u26a0 u062cu062fu0648u0644 product_images u063au064au0631 u0645u0648u062cu0648u062f</p>";
    }
    
    // u0627u0644u062au062du0642u0642 u0645u0646 u0627u0644u0627u0633u062au0639u0644u0627u0645u0627u062a u0641u064a index.php
    echo "<h2>u062au0634u062eu064au0635 u0627u0633u062au0639u0644u0627u0645u0627u062a SQL u0641u064a index.php</h2>";
    
    $indexContent = file_get_contents('../index.php');
    
    // u0641u062du0635 u0627u0644u0627u0633u062au0639u0644u0627u0645u0627u062a u0627u0644u062au064a u062au0633u062au062eu062fu0645 image_url
    preg_match_all('/SELECT.+?image_url.+?FROM.+?;/is', $indexContent, $matches);
    
    if (!empty($matches[0])) {
        echo "<p>u062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 " . count($matches[0]) . " u0627u0633u062au0639u0644u0627u0645 u064au0633u062au062eu062fu0645 image_url:</p>";
        
        foreach ($matches[0] as $index => $query) {
            echo "<div style='background:#f8f8f8; padding:10px; margin:10px 0; border-left:4px solid #ddd;'>";
            echo "<pre style='margin:0; overflow:auto;'>".htmlspecialchars($query)."</pre>";
            echo "</div>";
        }
    } else {
        echo "<p>u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0627u0633u062au0639u0644u0627u0645u0627u062a SQL u062au0633u062au062eu062fu0645 image_url u0641u064a index.php</p>";
    }
    
    // u0641u062du0635 u0627u0633u062au062eu062fu0627u0645u0627u062a image_url u0641u064a HTML
    preg_match_all('/\$product\[.image_url.\]/i', $indexContent, $matches);
    
    if (!empty($matches[0])) {
        echo "<p>u062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 " . count($matches[0]) . " u0627u0633u062au062eu062fu0627u0645 u0644u0640 image_url u0641u064a u0639u0631u0636 HTML:</p>";
    } else {
        echo "<p>u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0627u0633u062au062eu062fu0627u0645u0627u062a u0644u0640 image_url u0641u064a u0639u0631u0636 HTML</p>";
    }
    
    // u0627u0644u062au062du0642u0642 u0645u0646 u0645u0644u0641 product.php
    echo "<h2>u062au0634u062eu064au0635 u0645u0644u0641 product.php</h2>";
    
    if (file_exists('../product.php')) {
        $productContent = file_get_contents('../product.php');
        
        preg_match_all('/SELECT.+?image_url.+?FROM.+?;/is', $productContent, $matches);
        
        if (!empty($matches[0])) {
            echo "<p>u062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 " . count($matches[0]) . " u0627u0633u062au0639u0644u0627u0645 u064au0633u062au062eu062fu0645 image_url u0641u064a product.php:</p>";
            
            foreach ($matches[0] as $index => $query) {
                echo "<div style='background:#f8f8f8; padding:10px; margin:10px 0; border-left:4px solid #ddd;'>";
                echo "<pre style='margin:0; overflow:auto;'>".htmlspecialchars($query)."</pre>";
                echo "</div>";
            }
        } else {
            echo "<p>u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0627u0633u062au0639u0644u0627u0645u0627u062a SQL u062au0633u062au062eu062fu0645 image_url u0641u064a product.php</p>";
        }
    } else {
        echo "<p>u0645u0644u0641 product.php u063au064au0631 u0645u0648u062cu0648u062f</p>";
    }
    
    // u0627u0644u062au062du0642u0642 u0645u0646 u0645u0644u0641u0627u062a u0627u0644u0645u0634u0645u0648u0644u0629 u0641u064a index.php
    echo "<h2>u0627u0644u0645u0644u0641u0627u062a u0627u0644u0645u0634u0645u0648u0644u0629 u0641u064a index.php</h2>";
    
    preg_match_all('/include|require|include_once|require_once.+[\'"](.*?)[\'"];/i', $indexContent, $matches);
    
    if (!empty($matches[1])) {
        echo "<p>u0627u0644u0645u0644u0641u0627u062a u0627u0644u0645u0634u0645u0648u0644u0629:</p>";
        echo "<ul>";
        foreach ($matches[1] as $includedFile) {
            echo "<li>" . htmlspecialchars($includedFile) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0645u0644u0641u0627u062a u0645u0634u0645u0648u0644u0629 u0641u064a index.php</p>";
    }
    
    // u0627u0644u0627u0642u062au0631u0627u062du0627u062a u0644u062du0644 u0627u0644u0645u0634u0643u0644u0629
    echo "<h2>u0627u0644u062du0644u0648u0644 u0627u0644u0645u0642u062au0631u062du0629</h2>";
    echo "<ol>";
    echo "<li>u062au0639u062fu064au0644 u062cu0645u064au0639 u0627u0633u062au0639u0644u0627u0645u0627u062a SQL u0644u0625u0632u0627u0644u0629 u0625u0634u0627u0631u0627u062a u0625u0644u0649 image_url u0627u0644u062au064a u0644u0627 u062au062au0648u0627u0641u0642 u0645u0639 u0628u0646u064au0629 u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</li>";
    echo "<li>u062au0639u062fu064au0644 u0645u0644u0641 product.php u0644u0644u062au0639u0627u0645u0644 u0628u0634u0643u0644 u0635u062du064au062d u0645u0639 u0628u0646u064au0629 u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</li>";
    echo "<li>u062au062du062fu064au062b u0627u0644u0645u0644u0641u0627u062a u0627u0644u0645u0634u0645u0648u0644u0629 u0627u0644u062au064a u0642u062f u062au0633u062au062eu062fu0645 image_url</li>";
    echo "</ol>";
    
    // u0631u0627u0628u0637 u0627u0644u0639u0648u062fu0629
    echo "<p style='margin-top:30px;'><a href='../index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:4px;'>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629</a></p>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color:red; direction:rtl;'>u062du062fu062b u062eu0637u0623 u0641u064a u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a: " . $e->getMessage() . "</p>";
}
