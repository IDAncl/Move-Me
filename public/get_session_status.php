<?php
require_once '../includes/Itaidbh.inc.php';
header('Content-Type: application/json');

$token = $_GET['token'] ?? '';

if (!$token) {
    echo json_encode(['error' => 'No token']);
    exit;
}

$stmt = $pdo->prepare("SELECT is_active, chosen_driver_id FROM chat_sessions WHERE chat_token = ?");
$stmt->execute([$token]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($session);