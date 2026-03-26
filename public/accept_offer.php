<?php
session_start();
require_once '../includes/Itaidbh.inc.php';
require_once '../vendor/autoload.php'; 

use Twilio\Rest\Client;

header('Content-Type: application/json');

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$sid    = $_ENV['YOUR_TWILIO_SID'];
$token_auth  = $_ENV['YOUR_TWILIO_AUTH_TOKEN'];
$twilioWhatsappNumber = $_ENV['YOUR_TWILIO_PHONE_NUMBER'];

// Get POST data
$token = $_POST['token'] ?? '';
$driverName = $_POST['driver_name'] ?? '';

if (empty($token) || empty($driverName)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing token or driver name']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch delivery details AND the driver's actual ID and Phone
    $stmt = $pdo->prepare("
        SELECT 
            d.full_name AS customer_name, 
            d.moving_date, 
            d.preferred_time,
            u.id AS driver_id,
            u.phone AS driver_phone
        FROM chat_sessions cs
        JOIN deliveries d ON cs.delivery_id = d.id
        LEFT JOIN users u ON u.full_name = ? 
        WHERE cs.chat_token = ?
    ");
    $stmt->execute([$driverName, $token]);
    $details = $stmt->fetch();

    if (!$details || empty($details['driver_id'])) {
        throw new Exception("Driver not found in the users table.");
    }

    // 2. Update status and set the ACTUAL driver ID (not just the name)
    $update = $pdo->prepare("UPDATE chat_sessions SET chosen_driver_id = ?, is_active = 0 WHERE chat_token = ?");
    $update->execute([$details['driver_id'], $token]);

    if ($update->rowCount() > 0) {
        // 3. System message for the chat
        $msg = "המכרז נסגר! המוביל שנבחר: " . $driverName;
        $insertMsg = $pdo->prepare("INSERT INTO chat_messages (chat_token, sender_name, message) VALUES (?, 'System', ?)");
        $insertMsg->execute([$token, $msg]);

        // 4. WhatsApp Notification Logic
        /*if (!empty($details['driver_phone'])) {
            try {
                $client = new Client($sid, $token_auth);
                
                $formattedDate = date("d/m/Y", strtotime($details['moving_date']));
                $formattedTime = date("H:i", strtotime($details['preferred_time']));
                // Updated URL to use a standard host or your local IP
                $chatUrl = "http://172.20.10.2/itaiTalStartup/public/my_routes.php?token=" . $token;

                $whatsappBody = "מזל טוב {$driverName}! סגרת הובלה עם {$details['customer_name']}.\n";
                $whatsappBody .= "📅 מועד: {$formattedDate} בשעה {$formattedTime}.\n";
                $whatsappBody .= "🔗 לפרטים נוספים: {$chatUrl}";

                // --- ISRAELI PHONE FORMATTER FOR WHATSAPP ---
                $cleanPhone = preg_replace('/[^0-9]/', '', $details['driver_phone']);
                if (strpos($cleanPhone, '0') === 0) {
                    $cleanPhone = '972' . substr($cleanPhone, 1);
                }
                $finalWhatsappPhone = "whatsapp:+" . $cleanPhone;

                // Send via Twilio WhatsApp
                $client->messages->create($finalWhatsappPhone, [
                    'from' => $twilioWhatsappNumber,
                    'body' => $whatsappBody
                ]);

            } catch (Exception $e) {
                error_log("WhatsApp Error: " . $e->getMessage());
                // We don't roll back the DB just because the message failed
            }
        }*/

        $pdo->commit();
        echo json_encode(['status' => 'success']);
    } else {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Update failed or already closed.']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}