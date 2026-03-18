<?php
require_once '../includes/Itaidbh.inc.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $driverName = $_POST['driver_name'] ?? '';

    if (empty($token) || empty($driverName)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing information.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Get the final price from the last message sent by this driver for this token
        $priceStmt = $pdo->prepare("SELECT quote_price FROM chat_messages 
                                    WHERE chat_token = ? AND sender_name = ? AND quote_price IS NOT NULL 
                                    ORDER BY created_at DESC LIMIT 1");
        $priceStmt->execute([$token, $driverName]);
        $priceData = $priceStmt->fetch();
        $finalPrice = $priceData ? $priceData['quote_price'] : '0';

        // 2. Update the session to inactive and save the winner
        $updateStmt = $pdo->prepare("UPDATE chat_sessions SET is_active = 0, chosen_driver_id = ? WHERE chat_token = ?");
        $updateStmt->execute([$driverName, $token]);

        // 3. INSERT a system message into the chat history
        $systemMsg = "🤝 Booking Confirmed! The client has accepted the offer from $driverName for ₪$finalPrice. This chat is now closed.";
        $msgStmt = $pdo->prepare("INSERT INTO chat_messages (chat_token, sender_name, message) VALUES (?, 'System', ?)");
        $msgStmt->execute([$token, $systemMsg]);

        $pdo->commit();
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}