<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

$token = $_GET['token'] ?? '';
$amount = $_GET['amount'] ?? '0';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Checkout - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        .payment-btn:active { transform: scale(0.97); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">

    <div class="min-h-screen flex flex-col max-w-md mx-auto">
        
        <div class="p-6 flex items-center justify-between">
            <button onclick="history.back()" class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm border border-slate-100">
                <i class="fas fa-chevron-left text-slate-400 text-sm"></i>
            </button>
            <span class="font-bold text-sm tracking-tight">Checkout</span>
            <div class="w-10"></div> </div>

        <div class="px-6 pb-8 text-center">
            <div class="bg-indigo-600 w-20 h-20 rounded-[2.5rem] flex items-center justify-center mx-auto mb-4 shadow-xl shadow-indigo-200">
                <i class="fas fa-shield-check text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight">₪<?php echo $amount; ?></h1>
            <p class="text-slate-400 text-sm font-medium mt-1 uppercase tracking-widest">Platform Fee</p>
        </div>

        <div class="bg-white flex-grow rounded-t-[3rem] shadow-[0_-10px_40px_rgba(0,0,0,0.03)] p-8 border-t border-slate-100">
            
            <div class="space-y-4 mb-10">
                <div class="flex justify-between items-center bg-slate-50 p-4 rounded-2xl">
                    <span class="text-slate-500 text-xs font-bold uppercase">Driver</span>
                    <span class="text-slate-800 font-extrabold text-sm"><?php echo htmlspecialchars($details['chosen_driver_id']); ?></span>
                </div>
                
                <div class="flex items-start gap-3 px-2">
                    <div class="flex flex-col items-center py-1">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <div class="w-[1px] h-6 border-l border-dashed border-slate-300 my-1"></div>
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    </div>
                    <div class="text-[11px] leading-tight text-slate-400 font-bold uppercase space-y-4">
                        <p class="pt-0.5 truncate"><?php echo htmlspecialchars($details['pickup_location']); ?></p>
                        <p class="pt-1.5 truncate"><?php echo htmlspecialchars($details['delivery_location']); ?></p>
                    </div>
                </div>
            </div>

            <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-1">Secure Payment Methods</h2>
            
            <div class="space-y-3">
                <button onclick="processPayment('apple')" class="payment-btn w-full bg-black py-4 rounded-2xl flex items-center justify-center transition-all shadow-lg active:bg-slate-900">
                    <i class="fab fa-apple text-white text-2xl"></i>
                    <span class="text-white font-bold ml-1 text-lg">Pay</span>
                </button>

                <div class="grid grid-cols-2 gap-3">
                    <button onclick="processPayment('bit')" class="payment-btn bg-white border border-slate-100 p-4 rounded-2xl flex flex-col items-center justify-center gap-2 shadow-sm transition-all hover:bg-blue-50/50 group">
                        <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 font-black italic text-sm">bit</div>
                        <span class="text-[10px] font-bold text-slate-600 uppercase">Bit</span>
                    </button>

                    <button onclick="processPayment('paybox')" class="payment-btn bg-white border border-slate-100 p-4 rounded-2xl flex flex-col items-center justify-center gap-2 shadow-sm transition-all hover:bg-blue-50/50">
                        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white text-[8px] font-black italic">PayBox</div>
                        <span class="text-[10px] font-bold text-slate-600 uppercase">PayBox</span>
                    </button>
                </div>

                <button onclick="processPayment('card')" class="payment-btn w-full bg-white border border-slate-100 p-4 rounded-2xl flex items-center justify-between shadow-sm transition-all hover:border-indigo-200">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-500">
                            <i class="fas fa-credit-card text-sm"></i>
                        </div>
                        <span class="font-bold text-sm text-slate-700">Credit Card</span>
                    </div>
                    <i class="fas fa-chevron-right text-slate-300 text-xs"></i>
                </button>
            </div>

            <div class="mt-8 text-center">
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest flex items-center justify-center gap-2">
                    <i class="fas fa-lock text-emerald-500"></i>
                    End-to-End Encrypted
                </p>
            </div>
        </div>
    </div>

    <script>
    function processPayment(method) {
        // Here you would add your actual payment SDK triggers
        // For now, we redirect to success
        window.location.href = `success.php?token=<?php echo $token; ?>&status=paid`;
    }
    </script>
</body>
</html>