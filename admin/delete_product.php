<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Verify if the admin exists in the database
$stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    // Invalid admin ID, destroy session and redirect to login
    session_destroy();
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Get product image before deletion
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$id])) {
        // Delete the product image if it exists
        if ($product && $product['image'] && file_exists("../" . $product['image'])) {
            unlink("../" . $product['image']);
        }
    }
}

header('Location: dashboard.php');
exit(); 