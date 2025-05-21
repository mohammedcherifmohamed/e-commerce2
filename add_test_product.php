<?php
require_once 'config/db.php';

try {
    // Create test product
    $stmt = $pdo->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'Test Product',
        99.99,
        'This is a test product description',
        'uploads/test-product.jpg'
    ]);
    
    echo "Test product added successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 