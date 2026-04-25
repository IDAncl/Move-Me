<?php
session_start();
require_once 'Itaidbh.inc.php';
require_once '../vendor/autoload.php';

use GuzzleHttp\Client as GuzzleClient;
use Twilio\Rest\Client as TwilioClient;
use Dotenv\Dotenv;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup      = $_POST['pickup'] ?? '';
    $delivery    = $_POST['delivery'] ?? '';
    $moveDate    = $_POST['moveDate'] ?? '';
    $moveTime    = $_POST['moveTime'] ?? '';
    $objectType  = $_POST['objectType'] ?? '';
    $description = $_POST['description'] ?? '';
    $fullName    = $_POST['fullName'] ?? '';
    $phoneNumber = $_POST['phoneNumber'] ?? '';  

  
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    
    $sid    = $_ENV['YOUR_TWILIO_SID'];
    $token  = $_ENV['YOUR_TWILIO_AUTH_TOKEN'];
    $from   = $_ENV['YOUR_TWILIO_PHONE_NUMBER'];
    $apiKey = $_ENV['LLM_API_KEY']; 

  
    function getRegionFromLLM($location, $apiKey) {
        $client = new GuzzleClient();
        
        $systemInstruction = "You are a database assistant. Classify the following Israeli location into exactly one of these categories: 'north', 'south', 'east', 'center'. 
        Respond with ONLY the category word. No punctuation, no explanation.
        Example: 'Tel Aviv' -> 'center', 'Eilat' -> 'south', 'Metula' -> 'north', 'Jerusalem' -> 'center'.";

        try {
            $response = $client->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$apiKey", [
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => $systemInstruction . "\n\nLocation: " . $location]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1, 
                        'maxOutputTokens' => 5 
                    ]
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $region = strtolower(trim($data['candidates'][0]['content']['parts'][0]['text']));
            
            
            $allowed = ['north', 'south', 'east', 'center'];
            return in_array($region, $allowed) ? $region : "center";

        } catch (\Exception $e) {
            error_log("LLM Classification Error: " . $e->getMessage());
            return "center"; 
        }
    }
 
    try {
        
        $region = getRegionFromLLM($pickup, $apiKey);

        $pdo->beginTransaction();

        
        $sql = "INSERT INTO deliveries (full_name, phone_number, pickup_location, delivery_location, moving_date, preferred_time, items_description, object_type, region) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullName, $phoneNumber, $pickup, $delivery, $moveDate, $moveTime, $description, $objectType, $region]);
        
        $deliveryId = $pdo->lastInsertId();

        $chatToken = bin2hex(random_bytes(16)); 
        $chatSql = "INSERT INTO chat_sessions (delivery_id, chat_token, is_active) VALUES (?, ?, 1)";
        $chatStmt = $pdo->prepare($chatSql);
        $chatStmt->execute([$deliveryId, $chatToken]);

  
        try {

            $twilio = new TwilioClient($sid, $token);
            $chatUrl = "http://172.20.10.2/itaiTalStartup/public/chat.php?token=" . $chatToken;
            
            $messageBody = "היי $fullName, הבקשה שלך פורסמה ב-MoveMe! 🚚\nתוכל לעקוב אחרי הצעות מחיר וניווט כאן: $chatUrl";

            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
            if (strpos($cleanPhone, '0') === 0) {
                $cleanPhone = '972' . substr($cleanPhone, 1);
            }
            $whatsappTo = "whatsapp:+" . $cleanPhone;

            $twilio->messages->create(
                $whatsappTo,
                [
                    'from' => $from,
                    'body' => $messageBody
                ]
            );
        } catch (Exception $whatsappError) {
            error_log("WhatsApp Error: " . $whatsappError->getMessage());
        }

        $pdo->commit();


        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_name'] = $fullName;

   
        header("Location: ../public/chat.php?token=" . $chatToken);
        exit();
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("System Error: " . $e->getMessage());
    }
}