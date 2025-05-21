<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Check if the request contains JSON data
$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === 'application/json') {
    // Handle JSON data from checkout form
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400);
        die('Invalid JSON data');
    }

    $customer = $data['customer'];
    $items = $data['items'];
    $total = $data['total'];

    try {
        $pdo->beginTransaction();

        foreach ($items as $item) {
            // Insert order for each item
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    product_id, size_id, quantity, customer_name, phone, email, 
                    address, city, postal_code, notes, total_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $item_total = $item['price'] * $item['quantity'];
            
            $stmt->execute([
                $item['id'],
                $item['size_id'] ?? null,
                $item['quantity'],
                $customer['name'],
                $customer['phone'],
                $customer['email'],
                $customer['address'],
                $customer['city'],
                $customer['postal_code'],
                $customer['notes'] ?? '',
                $item_total
            ]);

            // Update stock if size is specified
            if (isset($item['size_id']) && $item['size_id']) {
                $stmt = $pdo->prepare("
                    UPDATE product_sizes 
                    SET stock = stock - ? 
                    WHERE product_id = ? AND size_id = ?
                ");
                $stmt->execute([$item['quantity'], $item['id'], $item['size_id']]);
            }

            // Send Telegram notification for each item
            $telegram_bot_id = "7971952794:AAEHtg5B5XbRjUs1UDNk47B7yvJmknsVJCs";
            $chat_id = "@yurei2_Bot2";
            
            $message = "
🛍️ New Order!

📦 Product Details:
- {$item['name']}
- Size: " . ($item['size'] ?? 'N/A') . "
- Quantity: {$item['quantity']}
💰 Total: \${$item_total}

👤 Customer Information:
Name: {$customer['name']}
📞 Phone: {$customer['phone']}
📧 Email: {$customer['email']}
🏠 Address: {$customer['address']}
🌆 City: {$customer['city']}
📮 Postal Code: {$customer['postal_code']}
📝 Notes: " . ($customer['notes'] ?? 'None') . "
            ";

            $telegram_api_url = "https://api.telegram.org/bot{$telegram_bot_id}/sendMessage";
            $telegram_data = [
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML'
            ];

            $ch = curl_init($telegram_api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($telegram_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            
            $telegram_response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Log Telegram response for debugging
            error_log("Telegram API Response: " . $telegram_response);
            error_log("HTTP Code: " . $http_code);
            if ($curl_error) {
                error_log("cURL Error: " . $curl_error);
            }
        }

        $pdo->commit();
        
        // Send success response for JSON requests
        echo json_encode(['success' => true]);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order Error: " . $e->getMessage());
        http_response_code(500);
        die('Error processing order: ' . $e->getMessage());
    }
} else {
    // Handle regular form data (single product order)
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $postal_code = $_POST['postal_code'];
    $notes = $_POST['notes'] ?? '';
    $size_id = $_POST['size_id'] ?? null;
    $quantity = (int)$_POST['quantity'] ?? 1;

    // Validate quantity
    if ($quantity < 1) {
        $quantity = 1;
    }

    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die('Product not found');
    }

    // Check stock availability
    if ($size_id) {
        $stmt = $pdo->prepare("SELECT stock FROM product_sizes WHERE product_id = ? AND size_id = ?");
        $stmt->execute([$product_id, $size_id]);
        $size_stock = $stmt->fetch();

        if (!$size_stock || $size_stock['stock'] < $quantity) {
            die('Not enough stock available');
        }
    }

    try {
        $pdo->beginTransaction();

        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                product_id, size_id, quantity, customer_name, phone, email, 
                address, city, postal_code, notes, total_price
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $total_price = $product['price'] * $quantity;
        
        $stmt->execute([
            $product_id, $size_id, $quantity, $name, $phone, $email,
            $address, $city, $postal_code, $notes, $total_price
        ]);

        // Update stock
        if ($size_id) {
            $stmt = $pdo->prepare("
                UPDATE product_sizes 
                SET stock = stock - ? 
                WHERE product_id = ? AND size_id = ?
            ");
            $stmt->execute([$quantity, $product_id, $size_id]);
        }

        // Get size name
        $size_name = '';
        if ($size_id) {
            $stmt = $pdo->prepare("SELECT name FROM sizes WHERE id = ?");
            $stmt->execute([$size_id]);
            $size = $stmt->fetch();
            $size_name = $size['name'];
        }

        // Send Telegram notification
        $telegram_bot_id = "7971952794:AAEHtg5B5XbRjUs1UDNk47B7yvJmknsVJCs";
        $chat_id = "@yurei2_Bot2";
        
        $message = "
🛍️ New Order!

📦 Product Details:
- {$product['name']}
- Size: {$size_name}
- Quantity: {$quantity}
💰 Total: \${$total_price}

👤 Customer Information:
Name: {$name}
📞 Phone: {$phone}
📧 Email: {$email}
🏠 Address: {$address}  
🌆 City: {$city}
📮 Postal Code: {$postal_code}
📝 Notes: {$notes}
        ";

        $telegram_api_url = "https://api.telegram.org/bot{$telegram_bot_id}/sendMessage";
        $telegram_data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init($telegram_api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $telegram_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $telegram_response = curl_exec($ch);
        curl_close($ch);

        $pdo->commit();
        
        // Redirect to success page
        header('Location: order_success.php');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die('Error processing order: ' . $e->getMessage());
    }
} 