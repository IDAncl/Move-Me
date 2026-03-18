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

// Temporary debug for previewing (as we did before)
if (!$data && $token === 'test') {
    $data = [
        'full_name' => 'Client Name',
        'chosen_driver_id' => 'Driver Name',
        'phone' => '050-123-4567',
        'pickup_location' => 'Tel Aviv',
        'delivery_location' => 'Haifa'
    ];
}

if (!$data) { die("Access Denied."); }

$isCustomer = ($userRole === 'customer');
$partnerHeader = $isCustomer ? "Your Driver" : "Client Details";
$partnerName = $isCustomer ? $data['chosen_driver_id'] : $data['full_name'];
$partnerPhone = $data['phone'] ?? '050-000-0000'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .success-bg { background: radial-gradient(circle at top, #ecfdf5 0%, #f8fafc 100%); }
    </style>
</head>
<body class="success-bg min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="bg-emerald-500 w-20 h-20 rounded-[2.5rem] flex items-center justify-center mx-auto mb-4 shadow-xl shadow-emerald-200">
                <i class="fas fa-check text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">All Set!</h1>
            <p class="text-slate-500 font-medium mt-1">Payment confirmed & move booked.</p>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-slate-50 overflow-hidden">
            
            <div class="p-8 pb-0">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4"><?php echo $partnerHeader; ?></h2>
                
                <div class="flex items-center gap-4 bg-slate-50 p-5 rounded-[2rem] border border-slate-100">
                    <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-indigo-100">
                        <?php echo strtoupper(substr($partnerName, 0, 1)); ?>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-lg leading-tight"><?php echo htmlspecialchars($partnerName); ?></h3>
                        <p class="text-indigo-600 font-bold text-sm"><?php echo htmlspecialchars($partnerPhone); ?></p>
                    </div>
                    <a href="tel:<?php echo $partnerPhone; ?>" class="ml-auto bg-white w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm text-emerald-500 border border-slate-100 hover:scale-110 active:scale-95 transition-all">
                        <i class="fas fa-phone-alt"></i>
                    </a>
                </div>
            </div>

            <div class="p-8">
                <div class="space-y-6 relative">
                    <div class="absolute left-[11px] top-2 w-[2px] h-12 bg-slate-100"></div>
                    
                    <div class="flex gap-4 items-start">
                        <div class="w-6 h-6 rounded-full bg-slate-100 border-4 border-white shadow-sm flex items-center justify-center z-10">
                            <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider leading-none mb-1">Pickup</p>
                            <p class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($data['pickup_location']); ?></p>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start">
                        <div class="w-6 h-6 rounded-full bg-indigo-50 border-4 border-white shadow-sm flex items-center justify-center z-10">
                            <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-wider leading-none mb-1">Destination</p>
                            <p class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($data['delivery_location']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="mt-10">
                    <a href="<?php echo $isCustomer ? 'index.php' : 'ItaiRegisteredDriver.php'; ?>" 
                       class="flex items-center justify-center bg-slate-900 text-white w-full py-5 rounded-[2rem] font-black text-xs tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200 active:scale-95">
                       BACK TO DASHBOARD
                    </a>
                </div>
            </div>
        </div>

        <p class="text-center text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-8">
            <i class="fas fa-shield-check text-emerald-500 mr-1"></i> 100% Secured by MoveMe
        </p>
    </div>

</body>
</html>