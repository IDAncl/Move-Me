<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

$token = $_GET['token'] ?? '';
$amount = $_GET['amount'] ?? '0'; // Passed from the accept_offer logic

// Fetch delivery details for the "Invoice" look
$stmt = $pdo->prepare("SELECT d.pickup_location, d.delivery_location, cs.chosen_driver_id 
                       FROM chat_sessions cs 
                       JOIN deliveries d ON cs.delivery_id = d.id 
                       WHERE cs.chat_token = ?");
$stmt->execute([$token]);
$details = $stmt->fetch();

if (!$details) { die("Invalid transaction."); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">

    <div class="max-w-md mx-auto p-6">
        <div class="text-center mb-8">
            <div class="bg-indigo-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-wallet text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">app fee</h1>
            <p class="text-gray-500 text-sm">Payment for Driver: <span class="font-bold text-indigo-600"><?php echo htmlspecialchars($details['chosen_driver_id']); ?></span></p>
        </div>

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-400 text-xs font-bold uppercase">Service</span>
                <span class="text-gray-400 text-xs font-bold uppercase">Price</span>
            </div>
            
            <div class="border-t border-dashed border-gray-100 pt-4 flex justify-between items-center">
                <span class="font-bold text-gray-800">Total to Pay</span>
                <span class="text-2xl font-black text-indigo-600">₪<?php echo $amount; ?></span>
            </div>
        </div>

        <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 ml-2">Select Payment Method</h2>
        <div class="space-y-3">
            
            <button onclick="processPayment('bit')" class="w-full bg-white border-2 border-transparent hover:border-blue-400 p-4 rounded-2xl flex items-center justify-between transition-all group shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 font-bold italic">bit</div>
                    <span class="font-bold text-gray-700">Pay with Bit</span>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-400"></i>
            </button>
            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow">
                <a href="success.php?token=<?php echo $token; ?>&status=paid" class="block">Preview Success Page</a>
            </button>

            <button onclick="processPayment('apple')" class="w-full bg-black p-4 rounded-2xl flex items-center justify-center transition-transform active:scale-95 shadow-lg">
                <i class="fab fa-apple text-white text-2xl mr-2"></i>
                <span class="text-white font-bold">Pay</span>
            </button>

            <button onclick="processPayment('card')" class="w-full bg-white border-2 border-transparent hover:border-indigo-400 p-4 rounded-2xl flex items-center justify-between transition-all group shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <span class="font-bold text-gray-700">Credit / Debit Card</span>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:text-indigo-400"></i>
            </button>

            <button onclick="processPayment('paybox')" class="w-full bg-white border-2 border-transparent hover:border-blue-600 p-4 rounded-2xl flex items-center justify-between transition-all group shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white text-[10px] font-black italic">PayBox</div>
                    <span class="font-bold text-gray-700">PayBox</span>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:text-blue-600"></i>
            </button>
        </div>

        <p class="text-center text-[10px] text-gray-400 mt-8 leading-relaxed">
            <i class="fas fa-lock mr-1"></i> Payments are secured and encrypted.<br>
            Powered by MoveMe Payment Gateway
        </p>
    </div>

    <script>
    function processPayment(method) {
        // Simulate payment success and redirect
        window.location.href = `success.php?token=<?php echo $token; ?>&status=paid`;
    }
    </script>
</body>
</html>