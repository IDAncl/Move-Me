<?php
require_once '../includes/Itaidbh.inc.php';

// Set header to return JSON so the JavaScript fetch works correctly
header('Content-Type: application/json');

// Inside send_message.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = $_POST['token'] ?? '';
    $sender  = $_POST['sender'] ?? 'Guest';
    $message = $_POST['message'] ?? ''; // This can now be an empty string
    $price   = !empty($_POST['quote_price']) ? $_POST['quote_price'] : null;

    // Only block if the token is missing
    if (empty($token)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Session']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (chat_token, sender_name, message, quote_price) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$token, $sender, $message, $price]);
    
    echo json_encode(['status' => $success ? 'success' : 'error']);
}