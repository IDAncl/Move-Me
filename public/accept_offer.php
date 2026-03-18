<?php
require_once '../includes/Itaidbh.inc.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $driverName = $_POST['driver_name'];

    // Mark chat as inactive and set the winner
    $sql = "UPDATE chat_sessions SET is_active = 0, chosen_driver_id = 
            (SELECT id FROM users WHERE user_name = ? LIMIT 1) 
            WHERE chat_token = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$driverName, $token]);

    echo json_encode(['status' => 'success']);
}