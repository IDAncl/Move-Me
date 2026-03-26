<?php
// 1. SET SESSION LIFETIME
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

// --- ISRAELI PHONE FORMATTER FOR TWILIO ---
if (!empty($phone)) {
    $phone = str_replace([' ', '-'], '', $phone);
    if (strpos($phone, '0') === 0) {
        $phone = '972' . substr($phone, 1);
    }
    $phone = str_replace('+', '', $phone);
    // Twilio requires 'whatsapp:+countrycode' format
    $whatsapp_to = "whatsapp:+" . $phone;
}

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$sid    = $_ENV['YOUR_TWILIO_SID'];;
$token  = $_ENV['YOUR_TWILIO_AUTH_TOKEN'];
$from   = $_ENV['YOUR_TWILIO_PHONE_NUMBER']; 


if ($action === 'send_code') {
    $mode = $_POST['mode'] ?? 'login';
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

    // --- TWILIO WHATSAPP SENDING LOGIC ---
    try {
        $twilio = new Client($sid, $token);
        $message = $twilio->messages->create(
            $whatsapp_to,
            [
                "from" => $from,
                "body" => "Your MoveMe verification code is: $code"
            ]
        );

        if ($message->sid) {
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Twilio Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'verify_code') {
    $code = $_POST['code'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND verification_code = ?");
    $stmt->execute([$phone, $code]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['is_driver'] = (int)$user['is_driver'];
        $_SESSION['user_role'] = ($user['is_driver'] == 1) ? 'driver' : 'customer';
        
        $pdo->prepare("UPDATE users SET verification_code = NULL WHERE id = ?")->execute([$user['id']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid code.']);
    }
}
?>