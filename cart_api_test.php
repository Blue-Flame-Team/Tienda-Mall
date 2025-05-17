<?php
// This is a simple test page to diagnose cart API issues
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart API Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        h2 { margin-top: 0; color: #333; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>Cart API Test Page</h1>
    
    <div class="card">
        <h2>Current Path Information</h2>
        <pre id="pathInfo">Loading...</pre>
    </div>
    
    <div class="card">
        <h2>Test Direct API Call</h2>
        <p>This will test accessing the cart API with a hard-coded path</p>
        <button id="testDirect">Test Direct API Call</button>
        <pre id="directResult">Click button to test...</pre>
    </div>
    
    <div class="card">
        <h2>Test Dynamic API Call</h2>
        <p>This will test the dynamic path resolution from cart.js</p>
        <button id="testDynamic">Test Dynamic API Call</button>
        <pre id="dynamicResult">Click button to test...</pre>
    </div>
    
    <script>
        // Display current path information
        document.getElementById('pathInfo').textContent = 
            "window.location.href: " + window.location.href + "\n" +
            "window.location.origin: " + window.location.origin + "\n" +
            "window.location.pathname: " + window.location.pathname + "\n" +
            "document.baseURI: " + document.baseURI;
        
        // Test direct API call with hardcoded path
        document.getElementById('testDirect').addEventListener('click', function() {
            const directUrl = window.location.origin + '/Tienda/api/get_cart.php';
            document.getElementById('directResult').textContent = "Fetching: " + directUrl + "\n\nWaiting for response...";
            
            fetch(directUrl)
                .then(response => {
                    document.getElementById('directResult').textContent += "\n\nResponse status: " + response.status;
                    return response.text();
                })
                .then(text => {
                    document.getElementById('directResult').textContent += "\n\nResponse body:\n" + text;
                    
                    try {
                        JSON.parse(text);
                        document.getElementById('directResult').textContent += "\n\n✅ Valid JSON response";
                    } catch(e) {
                        document.getElementById('directResult').textContent += "\n\n❌ Invalid JSON: " + e.message;
                    }
                })
                .catch(error => {
                    document.getElementById('directResult').textContent += "\n\nError: " + error.message;
                });
        });
        
        // Test getSiteBaseUrl function from cart.js 
        function getSiteBaseUrl() {
            // Force the base URL to always include 'Tienda' regardless of current page
            return window.location.protocol + '//' + window.location.host + '/Tienda/';
        }
        
        // Test dynamic API call
        document.getElementById('testDynamic').addEventListener('click', function() {
            const baseUrl = getSiteBaseUrl();
            const dynamicUrl = baseUrl + 'api/get_cart.php';
            
            document.getElementById('dynamicResult').textContent = 
                "Base URL: " + baseUrl + "\n" +
                "Full API URL: " + dynamicUrl + "\n\nWaiting for response...";
            
            fetch(dynamicUrl)
                .then(response => {
                    document.getElementById('dynamicResult').textContent += "\n\nResponse status: " + response.status;
                    return response.text();
                })
                .then(text => {
                    document.getElementById('dynamicResult').textContent += "\n\nResponse body:\n" + text;
                    
                    try {
                        JSON.parse(text);
                        document.getElementById('dynamicResult').textContent += "\n\n✅ Valid JSON response";
                    } catch(e) {
                        document.getElementById('dynamicResult').textContent += "\n\n❌ Invalid JSON: " + e.message;
                    }
                })
                .catch(error => {
                    document.getElementById('dynamicResult').textContent += "\n\nError: " + error.message;
                });
        });
    </script>
</body>
</html>
