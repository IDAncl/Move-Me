<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

$token = $_GET['token'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'guest';

// Fetch the full delivery and session data
$stmt = $pdo->prepare("
    SELECT d.*, cs.chosen_driver_id 
    FROM chat_sessions cs 
    JOIN deliveries d ON cs.delivery_id = d.id 
    WHERE cs.chat_token = ?
");
$stmt->execute([$token]);
$data = $stmt->fetch();

if (!$data) { die("Access Denied."); }

// Determine who the "Partner" is
$isCustomer = ($userRole === 'customer');
$partnerHeader = $isCustomer ? "Your Driver" : "Client Details";
$partnerName = $isCustomer ? $data['chosen_driver_id'] : $data['full_name'];
// Note: Ensure your 'deliveries' table has a 'phone' column for the client
$partnerPhone = $isCustomer ? "050-000-0000" : ($data['phone'] ?? 'Not provided'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmed - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
        <div class="bg-emerald-500 p-8 text-center">
            <div class="bg-white/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-inner">
                <i class="fas fa-check text-white text-3xl"></i>
            </div>
            <h1 class="text-white font-black text-2xl">Payment Successful!</h1>
            <p class="text-emerald-100 text-sm mt-1">Your move is officially booked.</p>
        </div>

        <div class="p-8">
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6"><?php echo $partnerHeader; ?></h2>
            
            <div class="flex items-center gap-4 mb-8 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                <div class="w-14 h-14 bg-indigo-600 rounded-full flex items-center justify-center text-white text-xl font-bold">
                    <?php echo strtoupper(substr($partnerName, 0, 1)); ?>
                </div>
                <div>
                    <h3 class="font-black text-gray-800 text-lg"><?php echo htmlspecialchars($partnerName); ?></h3>
                    <p class="text-indigo-600 font-bold text-sm"><?php echo htmlspecialchars($partnerPhone); ?></p>
                </div>
                <a href="tel:<?php echo $partnerPhone; ?>" class="ml-auto bg-white w-10 h-10 rounded-full flex items-center justify-center shadow-sm text-emerald-500 border border-gray-100 hover:scale-110 transition">
                    <i class="fas fa-phone"></i>
                </a>
            </div>

            <div class="space-y-4 mb-8">
                <div class="flex gap-3">
                    <i class="fas fa-map-marker-alt text-gray-300 mt-1"></i>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Pickup</p>
                        <p class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($data['pickup_location']); ?></p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <i class="fas fa-flag-checkered text-gray-300 mt-1"></i>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase">Delivery</p>
                        <p class="text-sm font-bold text-gray-700"><?php echo htmlspecialchars($data['delivery_location']); ?></p>
                    </div>
                </div>
            </div>

            <a href="<?php echo $isCustomer ? 'index.php' : 'ItaiRegisteredDriver.php'; ?>" 
               class="block w-full bg-gray-900 text-white text-center py-4 rounded-2xl font-black text-sm hover:bg-gray-800 transition shadow-lg">
               RETURN TO DASHBOARD
            </a>
        </div>
    </div>

</body>
</html>