<?php
session_start();
require_once '../includes/Itaidbh.inc.php';
header('Content-Type: application/json');

$token = $_POST['token'] ?? '';
$driverName = $_POST['driver_name'] ?? '';

if (empty($token) || empty($driverName)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing token or driver name']);
    exit;
}

try {
    // 1. עדכון הסטטוס ל-0 (סגור) וקביעת הנהג
    // הערה: אם chosen_driver_id הוא מספר ב-DB, וודא שאתה שולח ID ולא שם. 
    // אם זו עמודת טקסט, הקוד הזה יעבוד מצוין.
    $update = $pdo->prepare("UPDATE chat_sessions SET chosen_driver_id = ?, is_active = 0 WHERE chat_token = ?");
    $success = $update->execute([$driverName, $token]);

    if ($success && $update->rowCount() > 0) {
        // 2. שליחת הודעת מערכת כדי שהצ'אט יתרענן אצל כולם
        $msg = "המכרז נסגר! המוביל שנבחר: " . $driverName;
        $insertMsg = $pdo->prepare("INSERT INTO chat_messages (chat_token, sender_name, message) VALUES (?, 'System', ?)");
        $insertMsg->execute([$token, $msg]);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No session updated. Check if token is correct.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}