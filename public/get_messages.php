<?php
require_once '../includes/Itaidbh.inc.php';
header('Content-Type: application/json');

$token = $_GET['token'] ?? '';

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE chat_token = ? ORDER BY created_at ASC");
    $stmt->execute([$token]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
} else {
    echo json_encode([]);
}