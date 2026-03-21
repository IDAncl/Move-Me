<?php
// 1. SET SESSION LIFETIME (MUST be before session_start)
$timeout = 60 * 60 * 24 * 30; // 30 days
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params([
    'lifetime' => $timeout,
    'path' => '/',
    'domain' => '', 
    'secure' => false,     // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once '../includes/Itaidbh.inc.php';
require_once '../vendor/autoload.php'; 

use Twilio\Rest\Client;

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$phone = $_POST['phone'] ?? '';

// --- ISRAELI PHONE FORMATTER ---
if (!empty($phone)) {
    $phone = str_replace([' ', '-'], '', $phone);
    if (strpos($phone, '0') === 0) {
        $phone = '+972' . substr($phone, 1);
    }
    if (strpos($phone, '+') !== 0) {
        $phone = '+972' . $phone;
    }
}

// --- TWILIO CONFIG (Replace with your keys) ---
$sid    = 'AC8ca7a9265227314380f02d348c2f55b9';
$token  = '89f7c92a49060092b0c14a078fd1facc';
$from   = '+13185586971';


if ($action === 'send_code') {
    $mode = $_POST['mode'];
    $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if ($mode === 'login' && !$user) {
        echo json_encode(['success' => false, 'message' => 'Phone not registered.']);
        exit;
    }

    if ($mode === 'signup') {
        if ($user) { 
            echo json_encode(['success' => false, 'message' => 'Phone already exists.']); 
            exit; 
        }
        $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, vehicle_type, is_driver, verification_code) VALUES (?, ?, ?, 1, ?)");
        $stmt->execute([$_POST['name'], $phone, $_POST['vehicle'], $code]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE phone = ?");
        $stmt->execute([$code, $phone]);
    }

    try {
        $client = new Client($sid, $token);
        $client->messages->create($phone, ['from' => $from, 'body' => "Your MoveMe code: $code"]);
        echo json_encode(['success' => true]); 
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'SMS failed: ' . $e->getMessage()]);
    }
}

if ($action === 'verify_code') {
    $code = $_POST['code'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND verification_code = ?");
    $stmt->execute([$phone, $code]);
    $user = $stmt->fetch();

    if ($user) {
        // --- CRITICAL FIX: SET ALL NECESSARY SESSION VARIABLES ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['is_driver'] = (int)$user['is_driver'];
        
        // This line ensures the Chat page recognizes the role correctly
        $_SESSION['user_role'] = ($user['is_driver'] == 1) ? 'driver' : 'customer';
        
        $pdo->prepare("UPDATE users SET verification_code = NULL WHERE id = ?")->execute([$user['id']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid code.']);
    }
}
?>
