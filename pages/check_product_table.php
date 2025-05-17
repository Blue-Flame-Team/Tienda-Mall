<?php
require_once '../includes/bootstrap.php';

try {
    $db = Database::getInstance();
    $result = $db->query("DESCRIBE product");
    
    echo "<h2>Estructura de la tabla 'product'</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Ahora obtener los primeros registros para ver los datos reales
    $products = $db->query("SELECT * FROM product LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Ejemplo de registro de producto</h2>";
    echo "<pre>";
    print_r($products);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
