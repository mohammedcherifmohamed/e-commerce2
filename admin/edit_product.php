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

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: dashboard.php');
    exit();
}

// Get all sizes and current stock levels
$stmt = $pdo->prepare("
    SELECT s.*, COALESCE(ps.stock, 0) as stock
    FROM sizes s
    LEFT JOIN product_sizes ps ON ps.size_id = s.id AND ps.product_id = ?
    ORDER BY FIELD(s.name, 'XS', 'S', 'M', 'L', 'XL', 'XXL')
");
$stmt->execute([$id]);
$sizes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image_path = $product['image'];
    
    // Handle file upload if new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Delete old image if it exists
            if ($product['image'] && file_exists("../" . $product['image'])) {
                unlink("../" . $product['image']);
            }
            $image_path = 'uploads/' . $new_filename;
        }
    }

    try {
        $pdo->beginTransaction();

        // Update product
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, description = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $price, $description, $image_path, $id]);

        // Update product sizes
        $stmt_delete = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
        $stmt_delete->execute([$id]);

        $stmt_insert = $pdo->prepare("INSERT INTO product_sizes (product_id, size_id, stock) VALUES (?, ?, ?)");
        foreach ($sizes as $size) {
            $stock = (int)$_POST['stock_' . $size['id']] ?? 0;
            if ($stock > 0) {
                $stmt_insert->execute([$id, $size['id'], $stock]);
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
    <title>Edit Product - DarkStore</title>
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
                        <h2 class="text-center mb-4">Edit Product</h2>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Price</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                       value="<?php echo htmlspecialchars($product['price']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Product Image</label>
                                <?php if ($product['image']): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars('../' . $product['image']); ?>" 
                                             alt="Current product image" style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="form-text text-muted">Leave empty to keep current image</small>
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
                                                           value="<?php echo (int)$size['stock']; ?>" 
                                                           min="0">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Product</button>
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