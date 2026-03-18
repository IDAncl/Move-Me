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
        // We only update the chosen driver. 
        // is_active remains 1 so the chat stays open until payment.
        $updateStmt = $pdo->prepare("UPDATE chat_sessions SET chosen_driver_id = ? WHERE chat_token = ?");
        $updateStmt->execute([$driverName, $token]);

        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}