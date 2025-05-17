<?php
/**
 * Product Detail Page Fix
 * This script updates the product.php page with the correct cart API paths
 */

// File paths
$productPagePath = __DIR__ . '/product.php';

// Read the original file
$originalContent = file_get_contents($productPagePath);
if ($originalContent === false) {
    die("Error: Could not read the product.php file.");
}

// Fix #1: Update the main add-to-cart API endpoint
$originalAddToCartCode = "// AJAX call to add item to cart
                fetch('api/cart/add', {";
$newAddToCartCode = "// AJAX call to add item to cart with correct API path
                fetch(window.location.origin + '/Tienda/api/add_to_cart.php', {";
$updatedContent = str_replace($originalAddToCartCode, $newAddToCartCode, $originalContent);

// Fix #2: Update the success response check to match our new API format
$originalSuccessCheck = "if (data.status === 'success') {";
$newSuccessCheck = "if (data.success) {";
$updatedContent = str_replace($originalSuccessCheck, $newSuccessCheck, $updatedContent);

// Fix #3: Update the cart count update code
$originalCartCountCode = "cartCountElement.textContent = data.data.item_count;";
$newCartCountCode = "cartCountElement.textContent = data.cart.count;";
$updatedContent = str_replace($originalCartCountCode, $newCartCountCode, $updatedContent);

// Fix #4: Update related products add to cart API endpoint
$originalRelatedProductsCode = "// AJAX call to add item to cart
                fetch('api/cart/add', {";
$newRelatedProductsCode = "// AJAX call to add item to cart with correct API path
                fetch(window.location.origin + '/Tienda/api/add_to_cart.php', {";
$updatedContent = str_replace($originalRelatedProductsCode, $newRelatedProductsCode, $updatedContent);

// Write the updated content back to the file
if (file_put_contents($productPagePath, $updatedContent) === false) {
    die("Error: Could not write to the product.php file.");
}

// Create a backup copy just in case
file_put_contents($productPagePath . '.bak', $originalContent);

// Output success message
echo "Product Detail Page has been updated successfully!<br>";
echo "The following changes were made:<br>";
echo "1. Updated the cart API endpoint to use absolute URL path<br>";
echo "2. Fixed the success response check to match new API format<br>";
echo "3. Updated the cart count updating logic<br>";
echo "4. Updated related products add-to-cart functionality<br>";
echo "<br>A backup of the original file has been created at: product.php.bak";
?>
