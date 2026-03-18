<?php
require_once '../includes/Itaidbh.inc.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token   = $_POST['token'] ?? '';
    $sender  = $_POST['sender'] ?? 'Guest';
    $message = $_POST['message'] ?? ''; 
    $price   = !empty($_POST['quote_price']) ? $_POST['quote_price'] : null;

    if (empty($token)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Session']);
        exit;
    }

    // NEW: Check if the session is actually active
    $checkStmt = $pdo->prepare("SELECT is_active FROM chat_sessions WHERE chat_token = ?");
    $checkStmt->execute([$token]);
    $sessionStatus = $checkStmt->fetch();

    if (!$sessionStatus || (int)$sessionStatus['is_active'] === 0) {
        echo json_encode(['status' => 'error', 'message' => 'This chat is closed.']);
        exit;
    }

    // Only insert if active
    $stmt = $pdo->prepare("INSERT INTO chat_messages (chat_token, sender_name, message, quote_price) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$token, $sender, $message, $price]);
    
    echo json_encode(['status' => $success ? 'success' : 'error']);
}