<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Start HTML output
header('Content-Type: text/html; charset=utf-8');
echo "<html><head><title>u0625u0646u0634u0627u0621 u0635u0648u0631 u0627u062eu062au0628u0627u0631</title>";
echo "<style>body{font-family:Arial;direction:rtl;padding:20px;} .success{color:green;} .error{color:red;} .btn{background:#DB4444;color:white;padding:10px 15px;text-decoration:none;display:inline-block;margin:5px;border-radius:4px;}</style>";
echo "</head><body>";
echo "<h1>u0625u0646u0634u0627u0621 u0635u0648u0631 u0627u062eu062au0628u0627u0631 u0644u0644u0645u0646u062au062cu0627u062a</h1>";

// Create required directories
$directories = [
    $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads',
    $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads/products',
    $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets', 
    $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p class='success'>u062au0645 u0625u0646u0634u0627u0621 u0645u062cu0644u062f: $dir</p>";
        } else {
            echo "<p class='error'>u0641u0634u0644 u0625u0646u0634u0627u0621 u0645u062cu0644u062f: $dir</p>";
        }
    } else {
        echo "<p>u0645u062cu0644u062f u0645u0648u062cu0648u062f u0628u0627u0644u0641u0639u0644: $dir</p>";
    }
}

// Create placeholder image
$placeholderPath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images/product-placeholder.png';
if (!file_exists($placeholderPath)) {
    // Create a simple placeholder image
    $img = imagecreatetruecolor(300, 300);
    $bgColor = imagecolorallocate($img, 200, 200, 200);
    $textColor = imagecolorallocate($img, 50, 50, 50);
    imagefill($img, 0, 0, $bgColor);
    imagestring($img, 5, 85, 140, 'Product Placeholder', $textColor);
    imagepng($img, $placeholderPath);
    imagedestroy($img);
    echo "<p class='success'>u062au0645 u0625u0646u0634u0627u0621 u0635u0648u0631u0629 u0628u062fu064au0644u0629 u0641u064a: $placeholderPath</p>";
}

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get image paths from the database
    $stmt = $conn->query("SELECT * FROM product_images");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>u0625u0646u0634u0627u0621 u0635u0648u0631 u0644u0644u0645u0633u0627u0631u0627u062a u0627u0644u0645u0648u062cu0648u062fu0629 u0641u064a u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</h2>";
    
    $createdCount = 0;
    foreach ($images as $image) {
        if (isset($image['image_path']) && !empty($image['image_path'])) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/' . ltrim($image['image_path'], '/');
            
            // Create directory if it doesn't exist
            $directory = dirname($imagePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Only create if image doesn't exist
            if (!file_exists($imagePath)) {
                // Generate a dummy image
                $img = imagecreatetruecolor(300, 300);
                
                // Random background color
                $r = rand(100, 240);
                $g = rand(100, 240);
                $b = rand(100, 240);
                $bgColor = imagecolorallocate($img, $r, $g, $b);
                $textColor = imagecolorallocate($img, 50, 50, 50);
                
                imagefill($img, 0, 0, $bgColor);
                
                // Center the product and image IDs
                $text = "Product: {$image['product_id']}\nImage: {$image['image_id']}";
                $lines = explode("\n", $text);
                $y = 130;
                foreach ($lines as $line) {
                    $lineWidth = imagefontwidth(5) * strlen($line);
                    $x = (300 - $lineWidth) / 2;
                    imagestring($img, 5, $x, $y, $line, $textColor);
                    $y += 20;
                }
                
                // Detect file extension
                $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
                
                // Save the image with appropriate function
                switch (strtolower($extension)) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($img, $imagePath, 90);
                        break;
                    case 'png':
                        imagepng($img, $imagePath);
                        break;
                    case 'gif':
                        imagegif($img, $imagePath);
                        break;
                    default:
                        // Default to PNG if extension not recognized
                        imagepng($img, $imagePath);
                }
                
                imagedestroy($img);
                
                echo "<p class='success'>u062au0645 u0625u0646u0634u0627u0621 u0635u0648u0631u0629 u0641u064a: $imagePath</p>";
                $createdCount++;
            } else {
                echo "<p>u0627u0644u0635u0648u0631u0629 u0645u0648u062cu0648u062fu0629 u0628u0627u0644u0641u0639u0644: $imagePath</p>";
            }
        }
    }
    
    echo "<h3>u062au0645 u0625u0646u0634u0627u0621 $createdCount u0635u0648u0631u0629 u062cu062fu064au062fu0629</h3>";
    
} catch (PDOException $e) {
    echo "<p class='error'>u062eu0637u0623 u0641u064a u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php' class='btn'>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629</a></p>";
echo "</body></html>";
