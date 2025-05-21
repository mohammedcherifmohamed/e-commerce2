<?php
require_once 'config/db.php';

try {
    // Check if we can connect
    echo "<h2>Database Connection Status:</h2>";
    echo "Connected successfully to database: " . $dbname . "<br><br>";

    // Check if products table exists
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h2>Tables in database:</h2>";
    print_r($tables);
    echo "<br><br>";

    // Check products in the table
    echo "<h2>Products in database:</h2>";
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($products);
    echo "</pre>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 