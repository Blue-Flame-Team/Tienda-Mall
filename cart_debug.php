<?php
/**
 * Cart Debug Page
 * This page helps troubleshoot cart API issues
 */

// Set up page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart API Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        .debug-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .debug-title { font-weight: bold; margin-bottom: 10px; color: #0066cc; }
        .debug-content { background-color: #f5f5f5; padding: 10px; border-radius: 4px; }
        pre { margin: 0; white-space: pre-wrap; }
        .success { color: green; }
        .error { color: red; }
        button { padding: 8px 16px; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0055aa; }
    </style>
</head>
<body>
    <h1>Cart API Debug</h1>
    
    <div class="debug-section">
        <div class="debug-title">Session Cart Contents</div>
        <div class="debug-content">
            <pre><?php 
                session_start();
                echo "Session ID: " . session_id() . "\n\n";
                if (isset($_SESSION['cart'])) {
                    echo "Cart exists in session\n";
                    echo "Cart contents:\n";
                    echo json_encode($_SESSION['cart'], JSON_PRETTY_PRINT);
                } else {
                    echo "No cart in session";
                }
            ?></pre>
        </div>
    </div>
    
    <div class="debug-section">
        <div class="debug-title">API URLs</div>
        <div class="debug-content">
            <?php
                // Get the base URL for the site
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $uri = $_SERVER['REQUEST_URI'];
                $baseUrl = $protocol . $host;
                
                // Calculate Tienda path if in subfolder
                $tiendaPath = '';
                if (strpos($uri, '/Tienda/') !== false) {
                    $tiendaPath = '/Tienda';
                }
                
                $apiUrl = $baseUrl . $tiendaPath . '/api/get_cart.php';
                $addToCartUrl = $baseUrl . $tiendaPath . '/api/add_to_cart.php';
            ?>
            <p>Base URL: <code><?php echo $baseUrl . $tiendaPath; ?></code></p>
            <p>Get Cart API: <code><?php echo $apiUrl; ?></code></p>
            <p>Add to Cart API: <code><?php echo $addToCartUrl; ?></code></p>
            
            <div>
                <button id="testGetCart">Test Get Cart API</button>
                <button id="testAddToCart">Test Add to Cart API</button>
            </div>
        </div>
    </div>
    
    <div class="debug-section">
        <div class="debug-title">API Response</div>
        <div class="debug-content">
            <pre id="apiResponse">Click a button above to test the APIs...</pre>
        </div>
    </div>
    
    <div class="debug-section">
        <div class="debug-title">JavaScript Path Detection</div>
        <div class="debug-content">
            <pre id="pathInfo"></pre>
            <script>
                document.getElementById('pathInfo').textContent = 
                    "window.location.pathname: " + window.location.pathname + "\n" +
                    "Detected site base URL: " + getSiteBaseUrl();
                
                function getSiteBaseUrl() {
                    const pathParts = window.location.pathname.split('/');
                    let basePath = '';
                    
                    // Check if we're in a subdirectory of Tienda
                    const tiendaIndex = pathParts.findIndex(part => part.toLowerCase() === 'tienda');
                    
                    if (tiendaIndex !== -1) {
                        // We found 'Tienda' in the path, construct base URL up to it
                        basePath = '/';
                        for (let i = 1; i <= tiendaIndex; i++) {
                          if (pathParts[i]) {
                            basePath += pathParts[i] + '/';
                          }
                        }
                    }
                    
                    return window.location.protocol + '//' + window.location.host + basePath;
                }
            </script>
        </div>
    </div>
    
    <script>
        document.getElementById('testGetCart').addEventListener('click', function() {
            fetch(getSiteBaseUrl() + 'api/get_cart.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('apiResponse').textContent = data;
                    try {
                        // Try to parse as JSON to check format
                        JSON.parse(data);
                        document.getElementById('apiResponse').innerHTML = '<span class="success">Valid JSON:</span>\n' + data;
                    } catch (e) {
                        document.getElementById('apiResponse').innerHTML = '<span class="error">Invalid JSON:</span>\n' + data;
                    }
                })
                .catch(error => {
                    document.getElementById('apiResponse').innerHTML = '<span class="error">Error:</span>\n' + error;
                });
        });
        
        document.getElementById('testAddToCart').addEventListener('click', function() {
            fetch(getSiteBaseUrl() + 'api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: 1, // Using a test product ID
                    quantity: 1
                })
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('apiResponse').textContent = data;
                try {
                    // Try to parse as JSON to check format
                    JSON.parse(data);
                    document.getElementById('apiResponse').innerHTML = '<span class="success">Valid JSON:</span>\n' + data;
                } catch (e) {
                    document.getElementById('apiResponse').innerHTML = '<span class="error">Invalid JSON:</span>\n' + data;
                }
            })
            .catch(error => {
                document.getElementById('apiResponse').innerHTML = '<span class="error">Error:</span>\n' + error;
            });
        });
        
        function getSiteBaseUrl() {
            const pathParts = window.location.pathname.split('/');
            let basePath = '';
            
            // Check if we're in a subdirectory of Tienda
            const tiendaIndex = pathParts.findIndex(part => part.toLowerCase() === 'tienda');
            
            if (tiendaIndex !== -1) {
                // We found 'Tienda' in the path, construct base URL up to it
                basePath = '/';
                for (let i = 1; i <= tiendaIndex; i++) {
                  if (pathParts[i]) {
                    basePath += pathParts[i] + '/';
                  }
                }
            }
            
            return window.location.protocol + '//' + window.location.host + basePath;
        }
    </script>
</body>
</html>
