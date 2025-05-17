<?php
// Include necessary files
require_once '../includes/bootstrap.php';

// Function to check if a column exists in a table
function columnExists($tableName, $columnName) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to check if a table exists
function tableExists($tableName) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SHOW TABLES LIKE '{$tableName}'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Function to get table structure
function getTableStructure($tableName) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("DESCRIBE {$tableName}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Main diagnostics
echo "<h1>Diagnóstico y Corrección de Tablas de Productos e Imágenes</h1>";

// Check if product_image table exists
if (!tableExists('product_image')) {
    echo "<p>La tabla 'product_image' no existe. Creando tabla...</p>";
    
    try {
        $db = Database::getInstance();
        $db->query("CREATE TABLE IF NOT EXISTS product_image (
            image_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES product(product_id)
        )");
        echo "<p class='success'>✅ Tabla 'product_image' creada correctamente.</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error al crear la tabla 'product_image': " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>La tabla 'product_image' existe.</p>";
    
    // Check if image_path column exists
    if (!columnExists('product_image', 'image_path')) {
        echo "<p>La columna 'image_path' no existe en la tabla 'product_image'. Añadiendo columna...</p>";
        
        try {
            $db = Database::getInstance();
            $db->query("ALTER TABLE product_image ADD COLUMN image_path VARCHAR(255) NOT NULL AFTER product_id");
            echo "<p class='success'>✅ Columna 'image_path' añadida correctamente.</p>";
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error al añadir la columna 'image_path': " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>La columna 'image_path' existe en la tabla 'product_image'.</p>";
    }
}

// Check actual table structure
echo "<h2>Estructura actual de la tabla 'product_image':</h2>";
$structure = getTableStructure('product_image');
if (!empty($structure)) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    foreach ($structure as $column) {
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
} else {
    echo "<p class='error'>No se pudo obtener la estructura de la tabla.</p>";
}

// Check for actual images in the table
echo "<h2>Imágenes en la tabla 'product_image':</h2>";
try {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM product_image LIMIT 10");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($images) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        
        // Headers
        echo "<tr>";
        foreach (array_keys($images[0]) as $column) {
            echo "<th>{$column}</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($images as $image) {
            echo "<tr>";
            foreach ($image as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p>Mostrando " . count($images) . " imágenes de un total de " . $db->query("SELECT COUNT(*) FROM product_image")->fetchColumn() . ".</p>";
    } else {
        echo "<p>No hay imágenes en la tabla 'product_image'.</p>";
        
        // Offer to fix wishlist.php
        echo "<h2>Arreglar archivo wishlist.php</h2>";
        echo "<p>El archivo wishlist.php contiene un error en la consulta SQL. ¿Desea arreglarlo?</p>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='fix_wishlist' value='1'>";
        echo "<button type='submit'>Arreglar wishlist.php</button>";
        echo "</form>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error al consultar imágenes: " . $e->getMessage() . "</p>";
}

// Fix wishlist.php if requested
if (isset($_POST['fix_wishlist'])) {
    $wishlistFile = __DIR__ . '/wishlist.php';
    $content = file_get_contents($wishlistFile);
    
    // Check which column to use for image in product table
    $imageColumn = '';
    try {
        $db = Database::getInstance();
        if (columnExists('product', 'image')) {
            $imageColumn = 'image';
        } elseif (columnExists('product', 'image_url')) {
            $imageColumn = 'image_url';
        } elseif (columnExists('product', 'main_image')) {
            $imageColumn = 'main_image';
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error al verificar columnas de imagen: " . $e->getMessage() . "</p>";
    }
    
    if (!empty($imageColumn)) {
        // Replace the problematic query
        $oldQuery = "SELECT p.*, pi.image_path FROM product p 
                        LEFT JOIN product_image pi ON p.product_id = pi.product_id AND pi.is_primary = 1 
                        WHERE p.is_active = 'yes' AND p.is_deleted = 'no' 
                        ORDER BY RAND() LIMIT 4";
        
        $newQuery = "SELECT * FROM product 
                        WHERE is_active = 'yes' AND is_deleted = 'no' 
                        ORDER BY RAND() LIMIT 4";
        
        // Update the content with the new query
        $updatedContent = str_replace($oldQuery, $newQuery, $content);
        
        // Also update references to image_path if needed
        if (strpos($content, '$product[\'image_path\']') !== false) {
            $updatedContent = str_replace('$product[\'image_path\']', '$product[\'' . $imageColumn . '\']', $updatedContent);
        }
        
        if (file_put_contents($wishlistFile, $updatedContent)) {
            echo "<p class='success'>✅ Archivo wishlist.php actualizado correctamente.</p>";
            echo "<p>Se reemplazó la consulta SQL problemática por una que funciona con la estructura actual de la base de datos.</p>";
        } else {
            echo "<p class='error'>❌ Error al actualizar el archivo wishlist.php.</p>";
        }
    } else {
        echo "<p class='error'>No se pudo determinar la columna de imagen correcta en la tabla product.</p>";
    }
}

// Provide SQL to check the structure of product table
echo "<h2>Estructura de la tabla 'product':</h2>";
$productStructure = getTableStructure('product');
if (!empty($productStructure)) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";
    foreach ($productStructure as $column) {
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
} else {
    echo "<p class='error'>No se pudo obtener la estructura de la tabla 'product'.</p>";
}

// Offer to fix all pages with the same issue
echo "<h2>Buscar y Arreglar Consultas Similares</h2>";
echo "<p>Este mismo error podría existir en otros archivos. ¿Desea buscar y arreglar todas las ocurrencias?</p>";
echo "<form method='post'>";
echo "<input type='hidden' name='fix_all_files' value='1'>";
echo "<button type='submit'>Buscar y Arreglar Todos los Archivos</button>";
echo "</form>";

// Fix all files with the same issue if requested
if (isset($_POST['fix_all_files'])) {
    $filesWithIssue = [];
    $directoryToSearch = realpath(__DIR__ . '/..');
    
    // Find all PHP files
    $phpFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directoryToSearch),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($phpFiles as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getRealPath();
            $fileContent = file_get_contents($filePath);
            
            // Check for the problematic query
            if (strpos($fileContent, 'pi.image_path') !== false) {
                $filesWithIssue[] = $filePath;
            }
        }
    }
    
    echo "<h3>Archivos con el problema encontrado (" . count($filesWithIssue) . "):</h3>";
    
    if (count($filesWithIssue) > 0) {
        echo "<ul>";
        foreach ($filesWithIssue as $file) {
            echo "<li>" . basename($file) . " (" . $file . ")</li>";
        }
        echo "</ul>";
        
        echo "<form method='post'>";
        echo "<input type='hidden' name='confirm_fix_all' value='1'>";
        echo "<button type='submit'>Corregir Todos Estos Archivos</button>";
        echo "</form>";
    } else {
        echo "<p>No se encontraron más archivos con este problema.</p>";
    }
    
    // Save found files in session for later fixing
    $_SESSION['files_with_issue'] = $filesWithIssue;
}

// Confirm fixing all files
if (isset($_POST['confirm_fix_all']) && isset($_SESSION['files_with_issue'])) {
    $filesWithIssue = $_SESSION['files_with_issue'];
    $fixedFiles = [];
    
    // Check which column to use for image in product table
    $imageColumn = '';
    try {
        $db = Database::getInstance();
        if (columnExists('product', 'image')) {
            $imageColumn = 'image';
        } elseif (columnExists('product', 'image_url')) {
            $imageColumn = 'image_url';
        } elseif (columnExists('product', 'main_image')) {
            $imageColumn = 'main_image';
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error al verificar columnas de imagen: " . $e->getMessage() . "</p>";
    }
    
    if (!empty($imageColumn)) {
        foreach ($filesWithIssue as $file) {
            $content = file_get_contents($file);
            $updatedContent = $content;
            
            // Pattern to match LEFT JOIN with product_image
            $pattern = '/LEFT\s+JOIN\s+product_image\s+pi\s+ON\s+p\.product_id\s*=\s*pi\.product_id/i';
            
            // Replace with a simple query without the join
            if (preg_match($pattern, $updatedContent)) {
                $updatedContent = preg_replace($pattern, '/* Removed JOIN with product_image */', $updatedContent);
                
                // Replace pi.image_path references
                $updatedContent = str_replace('pi.image_path', 'p.' . $imageColumn, $updatedContent);
                $updatedContent = str_replace('$product[\'image_path\']', '$product[\'' . $imageColumn . '\']', $updatedContent);
                
                if (file_put_contents($file, $updatedContent)) {
                    $fixedFiles[] = $file;
                }
            }
        }
        
        echo "<h3>Archivos corregidos (" . count($fixedFiles) . "/" . count($filesWithIssue) . "):</h3>";
        if (count($fixedFiles) > 0) {
            echo "<ul>";
            foreach ($fixedFiles as $file) {
                echo "<li>" . basename($file) . " - <span style='color:green'>✓ Corregido</span></li>";
            }
            echo "</ul>";
        }
        
        // List files that couldn't be fixed
        $notFixedFiles = array_diff($filesWithIssue, $fixedFiles);
        if (count($notFixedFiles) > 0) {
            echo "<h3>Archivos que no se pudieron corregir (" . count($notFixedFiles) . "):</h3>";
            echo "<ul>";
            foreach ($notFixedFiles as $file) {
                echo "<li>" . basename($file) . " - <span style='color:red'>✗ No corregido</span></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p class='error'>No se pudo determinar la columna de imagen correcta en la tabla product.</p>";
    }
    
    // Clear session
    unset($_SESSION['files_with_issue']);
}

// Add styles for better readability
echo "
<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
    h1, h2, h3 { color: #333; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th { background-color: #f2f2f2; }
    td, th { padding: 10px; text-align: left; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; margin: 10px 0; }
    button:hover { background-color: #45a049; }
    form { margin: 20px 0; }
</style>
";
?>
