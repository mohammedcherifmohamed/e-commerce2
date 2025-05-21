<?php
require_once 'config/db.php';

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN'); // Replace with your bot token
define('TELEGRAM_CHAT_ID', 'YOUR_CHAT_ID'); // Replace with your chat ID

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid input data');
    }

    // Extract customer and order information
    $customer = $data['customer'];
    $items = $data['items'];
    $total = $data['total'];

    // Prepare order message for Telegram
    $message = "🛍️ *New Order Received!*\n\n";
    $message .= "👤 *Customer Information:*\n";
    $message .= "Name: {$customer['name']}\n";
    $message .= "Email: {$customer['email']}\n";
    $message .= "Phone: {$customer['phone']}\n";
    $message .= "Address: {$customer['address']}\n\n";
    
    $message .= "📦 *Order Items:*\n";
    foreach ($items as $item) {
        $message .= "- {$item['name']} (x{$item['quantity']}) - $" . number_format($item['price'] * $item['quantity'], 2) . "\n";
    }
    $message .= "\n💰 *Total Amount:* $" . number_format($total, 2);

    // Send message to Telegram
    $telegramUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $telegramData = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init($telegramUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $telegramData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $telegramResponse = curl_exec($ch);
    curl_close($ch);

    // Store order in database (optional)
    // $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, total_amount, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
    // $stmt->execute([
    //     $customer['name'],
    //     $customer['email'],
    //     $customer['phone'],
    //     $customer['address'],
    //     $total
    // ]);

    echo json_encode(['success' => true, 'message' => 'Order placed successfully!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 