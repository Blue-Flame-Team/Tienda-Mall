<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Check if image ID and product ID are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    header('Location: products.php?error=invalid_parameters');
    exit;
}

$image_id = (int)$_GET['id'];
$product_id = (int)$_GET['product_id'];

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Begin transaction
    $conn->beginTransaction();
    
    // First, get the image path to delete the actual file
    $stmt = $conn->prepare("SELECT image_path, image_url, is_primary FROM product_images WHERE image_id = :image_id AND product_id = :product_id");
    $stmt->bindParam(':image_id', $image_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        // Image not found, redirect back
        $conn->rollBack();
        header('Location: edit_product.php?id=' . $product_id . '&error=image_not_found');
        exit;
    }
    
    // Delete the image record from database
    $stmt = $conn->prepare("DELETE FROM product_images WHERE image_id = :image_id AND product_id = :product_id");
    $stmt->bindParam(':image_id', $image_id);
    $stmt->bindParam(':product_id', $product_id);
    $stmt->execute();
    
    // If it was a primary image, set another image as primary if available
    if ($image['is_primary']) {
        $stmt = $conn->prepare("SELECT MIN(image_id) as first_image_id FROM product_images WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['first_image_id']) {
            $stmt = $conn->prepare("UPDATE product_images SET is_primary = 1, is_main = 1 WHERE image_id = :image_id");
            $stmt->bindParam(':image_id', $result['first_image_id']);
            $stmt->execute();
        }
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Delete the actual image file if it exists
    $imagePath = $image['image_path'] ?? $image['image_url'] ?? '';
    if (!empty($imagePath)) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/' . ltrim($imagePath, '/');
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
    
    // Success, redirect back
    header('Location: edit_product.php?id=' . $product_id . '&success=image_deleted');
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error if active
    if ($conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Redirect with error
    header('Location: edit_product.php?id=' . $product_id . '&error=' . urlencode('حدث خطأ: ' . $e->getMessage()));
    exit;
}
