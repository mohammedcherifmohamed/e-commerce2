<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', 'token'); // Replace with your actual bot token
define('TELEGRAM_CHAT_ID', 'tokren'); // Replace with your actual chat ID

header('Content-Type: application/json');

try {
    // Validate bot token format
    if (!preg_match('/^\d+:[A-Za-z0-9_-]{35}$/', TELEGRAM_BOT_TOKEN)) {
        throw new Exception('Invalid bot token format');
    }

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
    
    // Customer Information
    $message .= "👤 *Customer Information:*\n";
    $message .= "Name: {$customer['name']}\n";
    $message .= "Email: {$customer['email']}\n";
    $message .= "Phone: {$customer['phone']}\n";
    $message .= "Address: {$customer['address']}\n";
    if (!empty($customer['notes'])) {
        $message .= "Notes: {$customer['notes']}\n";
    }
    $message .= "\n";
    
    // Order Items
    $message .= "📦 *Order Items:*\n";
    foreach ($items as $item) {
        $message .= "- {$item['name']}\n";
        $message .= "  Size: {$item['size']}\n";
        $message .= "  Color: {$item['color']}\n";
        $message .= "  Quantity: {$item['quantity']}\n";
        $message .= "  Price: $" . number_format($item['price'] * $item['quantity'], 2) . "\n\n";
    }
    
    // Total
    $message .= "💰 *Total Amount:* $" . number_format($total, 2);

    // First, verify the bot token by getting bot info
    $verifyUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getMe";
    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $verifyResponse = curl_exec($ch);
    curl_close($ch);

    $verifyData = json_decode($verifyResponse, true);
    if (!$verifyData['ok']) {
        throw new Exception('Invalid bot token: ' . ($verifyData['description'] ?? 'Unknown error'));
    }

    // Send message to Telegram
    $telegramUrl = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $telegramData = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];

    // Initialize cURL session
    $ch = curl_init($telegramUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $telegramData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Execute cURL request
    $telegramResponse = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        throw new Exception('Failed to send Telegram message: ' . curl_error($ch));
    }
    
    // Get HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Close cURL session
    curl_close($ch);
    
    // Check Telegram API response
    $responseData = json_decode($telegramResponse, true);
    if (!$responseData['ok']) {
        throw new Exception('Telegram API Error: ' . ($responseData['description'] ?? 'Unknown error'));
    }

    // Log successful message
    error_log("Order sent successfully to Telegram. Response: " . $telegramResponse);

    echo json_encode(['success' => true, 'message' => 'Order sent successfully']);

} catch (Exception $e) {
    // Log error
    error_log("Error sending order to Telegram: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 
