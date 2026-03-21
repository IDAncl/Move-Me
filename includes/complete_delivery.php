<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_name']) || $_SESSION['is_driver'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$token = $_POST['token'] ?? '';

try {
    // עדכון סטטוס המשלוח בטבלת deliveries דרך ה-token שב-chat_sessions
    $stmt = $pdo->prepare("
        UPDATE deliveries d
        JOIN chat_sessions cs ON d.id = cs.delivery_id
        SET d.status = 'completed'
        WHERE cs.chat_token = ?
    ");
    $stmt->execute([$token]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}