<?php
/**
 * Database Debug Tool
 * Esta herramienta nos ayuda a diagnosticar problemas de base de datos
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';

// Define development mode for this script only
define('DEV_MODE', true);

// Inicializar variables
$error = null;
$tables = [];
$selectedTable = '';
$tableStructure = [];
$records = [];

try {
    // Initialize DB connection
    $db = Database::getInstance();
    
    // Get list of tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // If a table is selected, show its structure and some data
    if (isset($_GET['table']) && in_array($_GET['table'], $tables)) {
        $selectedTable = $_GET['table'];
        
        // Get table structure
        $stmt = $db->query("DESCRIBE {$selectedTable}");
        $tableStructure = $stmt->fetchAll();
        
        // Get sample data (limited to 10 records)
        $stmt = $db->query("SELECT * FROM {$selectedTable} LIMIT 10");
        $records = $stmt->fetchAll();
    }
    
    // If a test query is submitted
    if (isset($_POST['test_query']) && !empty($_POST['sql_query'])) {
        $query = $_POST['sql_query'];
        $stmt = $db->query($query);
        $testResults = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get error log if available
$errorLog = [];
$logFile = '../logs/database_errors.log';
if (file_exists($logFile)) {
    $errorLog = array_slice(file($logFile), -50); // Get last 50 lines
    $errorLog = array_reverse($errorLog); // Most recent first
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Debug - Tienda</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .error {
            background-color: #ffecec;
            color: #f44336;
            padding: 10px;
            border-left: 4px solid #f44336;
            margin-bottom: 20px;
        }
        .success {
            background-color: #e7f7e7;
            color: #4CAF50;
            padding: 10px;
            border-left: 4px solid #4CAF50;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .tables-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tables-list a {
            text-decoration: none;
            background-color: #f2f2f2;
            padding: 5px 10px;
            border-radius: 4px;
            color: #333;
        }
        .tables-list a:hover, .tables-list a.active {
            background-color: #4CAF50;
            color: white;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            overflow: auto;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .query-form {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f8f8;
            border-radius: 4px;
        }
        .query-form textarea {
            width: 100%;
            height: 100px;
            margin-bottom: 10px;
            padding: 8px;
            font-family: monospace;
        }
        .query-form button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 4px;
        }
        .tab-container {
            margin-top: 20px;
        }
        .tab-buttons {
            display: flex;
            gap: 5px;
            margin-bottom: 10px;
        }
        .tab-button {
            padding: 8px 16px;
            background-color: #f2f2f2;
            border: none;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
        }
        .tab-button.active {
            background-color: #4CAF50;
            color: white;
        }
        .tab-content {
            display: none;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 0 0 4px 4px;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Debug Tool</h1>
        
        <?php if ($error): ?>
            <div class="error">
                <h3>Error:</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="openTab(event, 'tab-tables')">Tables Explorer</button>
                <button class="tab-button" onclick="openTab(event, 'tab-query')">Test Query</button>
                <button class="tab-button" onclick="openTab(event, 'tab-errors')">Error Log</button>
            </div>
            
            <!-- Tables Explorer Tab -->
            <div id="tab-tables" class="tab-content active">
                <h2>Database Tables</h2>
                
                <div class="tables-list">
                    <?php foreach ($tables as $table): ?>
                        <a href="?table=<?php echo htmlspecialchars($table); ?>" class="<?php echo $table === $selectedTable ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($table); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($selectedTable): ?>
                    <h3>Table Structure: <?php echo htmlspecialchars($selectedTable); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableStructure as $column): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($column['Field']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Null']); ?></td>
                                    <td><?php echo htmlspecialchars($column['Key']); ?></td>
                                    <td><?php echo isset($column['Default']) ? htmlspecialchars($column['Default']) : 'NULL'; ?></td>
                                    <td><?php echo htmlspecialchars($column['Extra']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <h3>Sample Data (10 records)</h3>
                    <?php if (!empty($records)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($records[0]) as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <?php foreach ($record as $value): ?>
                                            <td><?php echo is_null($value) ? 'NULL' : htmlspecialchars(substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '')); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No records found in this table.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Select a table from the list above to view its structure and data.</p>
                <?php endif; ?>
            </div>
            
            <!-- Test Query Tab -->
            <div id="tab-query" class="tab-content">
                <h2>Test SQL Query</h2>
                
                <form method="post" class="query-form">
                    <textarea name="sql_query" placeholder="Enter your SQL query here..."><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
                    <button type="submit" name="test_query">Execute Query</button>
                </form>
                
                <?php if (isset($testResults)): ?>
                    <h3>Query Results</h3>
                    <?php if (!empty($testResults)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($testResults[0]) as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testResults as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?php echo is_null($value) ? 'NULL' : htmlspecialchars(substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '')); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Query executed successfully but returned no results.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Error Log Tab -->
            <div id="tab-errors" class="tab-content">
                <h2>Database Error Log</h2>
                
                <?php if (!empty($errorLog)): ?>
                    <pre><?php echo htmlspecialchars(implode('', $errorLog)); ?></pre>
                <?php else: ?>
                    <p>No error logs found or log file doesn't exist.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <p><a href="../index.php">Return to Homepage</a></p>
    </div>
    
    <script>
        function openTab(evt, tabId) {
            // Hide all tab content
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Remove active class from all tab buttons
            var tabButtons = document.getElementsByClassName("tab-button");
            for (var i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }
            
            // Show the selected tab and mark its button as active
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>
