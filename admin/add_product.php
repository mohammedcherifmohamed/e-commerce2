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

// Get all sizes
$stmt = $pdo->query("SELECT * FROM sizes ORDER BY FIELD(name, 'XS', 'S', 'M', 'L', 'XL', 'XXL')");
$sizes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    
    // Handle file upload
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = 'uploads/' . $new_filename;
        }
    }

    try {
        $pdo->beginTransaction();

        // Insert product
        $stmt = $pdo->prepare("INSERT INTO products (name, price, description, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $description, $image_path]);
        $product_id = $pdo->lastInsertId();

        // Insert product sizes
        $stmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size_id, stock) VALUES (?, ?, ?)");
        foreach ($sizes as $size) {
            $stock = (int)$_POST['stock_' . $size['id']] ?? 0;
            if ($stock > 0) {
                $stmt->execute([$product_id, $size['id'], $stock]);
            }
        }

        $pdo->commit();
        header('Location: dashboard.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - DarkStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-dark text-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Add New Product</h2>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Size Options</label>
                                <div class="row g-3">
                                    <?php foreach ($sizes as $size): ?>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($size['name']); ?></h6>
                                                <div class="input-group">
                                                    <span class="input-group-text">Stock</span>
                                                    <input type="number" 
                                                           class="form-control" 
                                                           name="stock_<?php echo $size['id']; ?>" 
                                                           value="0" 
                                                           min="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Add Product</button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 