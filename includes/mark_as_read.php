<?php
session_start();
require_once 'Itaidbh.inc.php';

$token = $_GET['token'] ?? '';
$redirect = $_GET['redirect'] ?? 'my_routes.php';

if ($token) {
    $stmt = $pdo->prepare("UPDATE chat_sessions SET driver_notified = 1 WHERE chat_token = ?");
    $stmt->execute([$token]);
}

header("Location: " . $redirect . "?token=" . $token);
exit();