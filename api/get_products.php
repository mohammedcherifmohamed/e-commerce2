<?php
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add full URL path to images
    foreach ($products as &$product) {
        if ($product['image']) {
            $product['image'] = '../' . $product['image'];
        }
    }
    
    echo json_encode($products);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 