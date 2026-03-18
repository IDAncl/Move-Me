<?php
session_start();
require_once '../includes/Itaidbh.inc.php'; 

// Helper function for Google Maps Travel Time
function getTravelTime($origin, $destination, $moveDate, $moveTime) {
    $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; 
    $departureTimestamp = strtotime("$moveDate $moveTime");
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($origin) . 
           "&destinations=" . urlencode($destination) . 
           "&departure_time=" . $departureTimestamp . 
           "&traffic_model=best_guess" .
           "&key=" . $apiKey;

    $response = @file_get_contents($url);
    $data = json_decode($response, true);
    return ($data && $data['status'] === 'OK') ? ($data['rows'][0]['elements'][0]['duration_in_traffic']['text'] ?? 'N/A') : 'N/A';
}

$driverName = $_SESSION['user_name'] ?? 'Driver';
$profilePic = "https://ui-avatars.com/api/?name=" . urlencode($driverName) . "&background=6366f1&color=fff&bold=true"; 

try {
    $sql = "SELECT d.*, cs.chat_token 
            FROM deliveries d 
            LEFT JOIN chat_sessions cs ON d.id = cs.delivery_id 
            WHERE cs.is_active = 1 OR cs.is_active IS NULL
            ORDER BY d.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Driver Dashboard | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .glass-sidebar { background: #1e293b; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .active-nav { background: rgba(255, 255, 255, 0.1); border-left: 4px solid #6366f1; }
    </style>
</head>
<body class="min-h-screen text-slate-900">

    <div class="md:hidden bg-white/80 backdrop-blur-md border-b px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2 text-xl font-black text-indigo-600 tracking-tighter">
            <i class="fas fa-truck-fast"></i> MoveMe
        </div>
        <button id="menuBtn" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-xl text-slate-600">
            <i class="fas fa-bars-staggered"></i>
        </button>
    </div>

    <aside id="sidebar" class="glass-sidebar w-72 text-white flex flex-col fixed h-full z-40 transform -translate-x-full transition-all duration-300 md:translate-x-0 shadow-2xl">
        <div class="p-8">
            <div class="flex items-center gap-3 text-2xl font-black tracking-tighter mb-12">
                <i class="fas fa-truck-fast text-indigo-400"></i> MoveMe
            </div>
            
            <nav class="space-y-2">
                <a href="#" class="active-nav flex items-center gap-4 p-4 rounded-2xl transition-all">
                    <i class="fas fa-grid-2 w-5 text-indigo-400"></i> 
                    <span class="font-bold text-sm">Dashboard</span>
                </a>
                <a href="my_routes.php" class="flex items-center gap-4 p-4 hover:bg-white/5 rounded-2xl transition-all group">
                    <i class="fas fa-route w-5 text-slate-400 group-hover:text-white"></i> 
                    <span class="font-bold text-sm text-slate-300 group-hover:text-white">My Routes</span>
                </a>
                <a href="#" class="flex items-center gap-4 p-4 hover:bg-white/5 rounded-2xl transition-all group">
                    <i class="fas fa-envelope w-5 text-slate-400 group-hover:text-white"></i> 
                    <span class="font-bold text-sm text-slate-300 group-hover:text-white">Messages</span>
                </a>
            </nav>
        </div>

        <div class="mt-auto p-8">
            <a href="logout.php" class="flex items-center gap-4 p-4 bg-red-500/10 text-red-400 rounded-2xl hover:bg-red-500 hover:text-white transition-all font-bold text-sm">
                <i class="fas fa-power-off"></i> Logout
            </a>
        </div>
    </aside>

    <div id="overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-30 hidden md:hidden"></div>

    <main class="md:ml-72 p-6 md:p-10">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Hey, <?php echo htmlspecialchars($driverName); ?>! 👋</h1>
                <p class="text-slate-500 font-medium">Ready to find your next route?</p>
            </div>
            <div class="flex items-center gap-4 bg-white p-2 pr-6 rounded-3xl shadow-sm border border-slate-100">
                <img src="<?php echo $profilePic; ?>" class="w-10 h-10 rounded-2xl shadow-inner" alt="Profile">
                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 leading-none">Verified Driver</p>
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($driverName); ?></p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                    <i class="fas fa-wallet text-6xl text-indigo-600"></i>
                </div>
                <p class="text-slate-400 text-xs font-black uppercase tracking-widest">Earnings</p>
                <h2 class="text-3xl font-black text-slate-900 mt-2">₪2,450</h2>
            </div>
            
            <div class="bg-indigo-600 p-6 rounded-[2rem] shadow-xl shadow-indigo-100 text-white relative overflow-hidden">
                <p class="text-indigo-200 text-xs font-black uppercase tracking-widest">Open Jobs</p>
                <h2 class="text-3xl font-black mt-2"><?php echo count($deliveries); ?></h2>
            </div>

            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100">
                <p class="text-slate-400 text-xs font-black uppercase tracking-widest">Driver Rating</p>
                <div class="flex items-center gap-2 mt-2">
                    <h2 class="text-3xl font-black text-slate-900">4.9</h2>
                    <div class="flex text-amber-400 text-xs gap-0.5">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="flex items-center justify-between px-2">
                <h3 class="text-xl font-extrabold text-slate-900">Available Jobs</h3>
                <div class="flex gap-2">
                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-3 py-1.5 rounded-full uppercase tracking-tighter">Live Updates</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <?php foreach ($deliveries as $row): ?>
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-50 hover:shadow-xl hover:shadow-slate-200/50 transition-all group">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        
                        <div class="flex-grow space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black bg-slate-100 text-slate-500 px-3 py-1 rounded-lg uppercase"><?php echo htmlspecialchars($row['object_type'] ?? 'General'); ?></span>
                                <span class="text-[10px] font-black bg-amber-50 text-amber-600 px-3 py-1 rounded-lg uppercase">
                                    <i class="fas fa-calendar-day mr-1"></i><?php echo date('M d', strtotime($row['moving_date'])); ?>
                                </span>
                            </div>

                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                                    <span class="font-extrabold text-slate-800 text-lg"><?php echo htmlspecialchars($row['pickup_location']); ?></span>
                                </div>
                                <div class="w-0.5 h-4 border-l-2 border-dashed border-slate-200 ml-1"></div>
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                    <span class="font-extrabold text-slate-800 text-lg"><?php echo htmlspecialchars($row['delivery_location']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row md:flex-col gap-3 min-w-[180px]">
                            <div class="text-center md:text-right px-4">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Est. Drive Time</p>
                                <p class="text-sm font-black text-slate-900"><?php echo getTravelTime($row['pickup_location'], $row['delivery_location'], $row['moving_date'], $row['preferred_time']); ?></p>
                            </div>
                            
                            <a href="chat.php?token=<?php echo $row['chat_token']; ?>" class="flex items-center justify-center gap-2 bg-slate-900 text-white py-4 px-6 rounded-2xl font-black text-xs hover:bg-indigo-600 transition-all shadow-lg active:scale-95">
                                CONTACT & QUOTE <i class="fas fa-chevron-right text-[10px] opacity-50"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function toggleMenu() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        menuBtn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    </script>
</body>
</html>