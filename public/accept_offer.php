<?php
session_start();
require_once '../includes/Itaidbh.inc.php';
header('Content-Type: application/json');

$token = $_POST['token'] ?? '';
$driverName = $_POST['driver_name'] ?? '';

if (empty($token) || empty($driverName)) {
    echo json_encode(['status' => 'error', 'message' => 'מידע חסר: טוקן או שם נהג']);
    exit;
}

try {
    // בדיקה שהסשן קיים ופעיל
    $stmt = $pdo->prepare("SELECT id FROM chat_sessions WHERE chat_token = ? AND is_active = 1");
    $stmt->execute([$token]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'ההצעה כבר לא רלוונטית או שהצ\'אט נסגר']);
        exit;
    }

    // עדכון הנהג שנבחר
    $update = $pdo->prepare("UPDATE chat_sessions SET chosen_driver_id = ? WHERE chat_token = ?");
    $update->execute([$driverName, $token]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}