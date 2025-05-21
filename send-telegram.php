<?php
// Replace these with your actual Telegram bot credentials
$botToken = 'YOUR_BOT_TOKEN';
$chatId = 'YOUR_CHAT_ID';

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$message = $data['message'];

// Telegram Bot API URL
$url = "https://api.telegram.org/bot{$botToken}/sendMessage";

// Prepare the data
$postData = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'HTML'
];

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Send the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if the message was sent successfully
if ($httpCode === 200) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}
?> 