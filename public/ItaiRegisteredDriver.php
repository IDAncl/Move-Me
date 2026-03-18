<?php
session_start();
require_once '../includes/Itaidbh.inc.php'; 

// Helper function for Google Maps Travel Time
function getTravelTime($origin, $destination, $moveDate, $moveTime) {
    $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // Replace with your actual key
    
    $departureTimestamp = strtotime("$moveDate $moveTime");
    
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($origin) . 
           "&destinations=" . urlencode($destination) . 
           "&departure_time=" . $departureTimestamp . 
           "&traffic_model=best_guess" .
           "&key=" . $apiKey;

    $response = @file_get_contents($url);
    $data = json_decode($response, true);

    if ($data && $data['status'] === 'OK') {
        return $data['rows'][0]['elements'][0]['duration_in_traffic']['text'] ?? 'N/A';
    }
    return 'N/A';
}

$driverName = $_SESSION['user_name'] ?? 'Driver';
$profilePic = "https://i.pravatar.cc/150?u=" . strtolower($driverName); 

try {
    // JOIN with chat_sessions to get the active token for each delivery
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .sidebar { background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="flex flex-col md:flex-row min-h-screen">

    <div class="md:hidden bg-white border-b px-4 py-3 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2 text-xl font-bold text-indigo-600">
            <i class="fas fa-truck-moving"></i> MoveMe
        </div>
        <button id="menuBtn" class="text-gray-600 text-2xl">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <aside id="sidebar" class="sidebar w-64 text-white flex flex-col p-6 fixed h-full z-40 transform -translate-x-full transition-transform duration-300 md:translate-x-0">
        <div class="flex items-center gap-2 text-2xl font-bold mb-10 hidden md:flex">
            <i class="fas fa-truck-moving"></i> MoveMe
        </div>
        <nav class="flex-grow space-y-4">
            <a href="#" class="flex items-center gap-3 p-3 bg-white/20 rounded-lg"><i class="fas fa-th-large w-5"></i> Dashboard</a>
            <a href="my_routes.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg transition">
                <i class="fas fa-route w-5"></i> My Routes
            </a>
            <a href="#" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg transition"><i class="fas fa-comment-dots w-5"></i> Messages</a>
        </nav>
        <div class="pt-6 border-t border-white/20">
            <a href="logout.php" class="flex items-center gap-3 p-3 hover:bg-red-500/40 rounded-lg transition"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <div id="overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden"></div>

    <main class="flex-grow md:ml-64 p-4 md:p-8">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($driverName); ?>!</h1>
                <p class="text-gray-500 text-sm">Here’s what’s happening today.</p>
            </div>
            <div class="flex items-center gap-3 bg-white p-2 pr-4 rounded-full shadow-sm border self-end md:self-auto">
                <img src="<?php echo $profilePic; ?>" class="w-8 h-8 rounded-full" alt="Profile">
                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($driverName); ?></span>
            </div>
        </header>

        <div class="flex md:grid md:grid-cols-3 gap-4 mb-8 overflow-x-auto no-scrollbar pb-2">
            <div class="bg-white p-5 rounded-2xl shadow-sm border-b-4 border-indigo-500 min-w-[250px] md:min-w-0">
                <p class="text-gray-400 text-xs font-semibold uppercase">Total Earnings</p>
                <h2 class="text-2xl font-bold text-gray-800 mt-1">$2,450.00</h2>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-b-4 border-purple-500 min-w-[250px] md:min-w-0">
                <p class="text-gray-400 text-xs font-semibold uppercase">Open Requests</p>
                <h2 class="text-2xl font-bold text-gray-800 mt-1"><?php echo count($deliveries); ?></h2>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border-b-4 border-pink-500 min-w-[250px] md:min-w-0">
                <p class="text-gray-400 text-xs font-semibold uppercase">Rating</p>
                <h2 class="text-2xl font-bold text-gray-800 mt-1">4.9 <span class="text-yellow-400"><i class="fas fa-star text-sm"></i></span></h2>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Available Jobs</h3>
                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-1 rounded">NEAR YOU</span>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase font-bold">
                        <tr>
                            <th class="px-6 py-4">Route & Est. Time</th>
                            <th class="px-6 py-4">Item</th>
                            <th class="px-6 py-4">Schedule</th>
                            <th class="px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-sm">
                        <?php foreach ($deliveries as $row): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($row['pickup_location']); ?></span>
                                        <i class="fas fa-long-arrow-alt-right text-indigo-400"></i>
                                        <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($row['delivery_location']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-[11px] text-orange-600 font-bold flex items-center gap-1">
                                            <i class="fas fa-clock"></i> Est. Drive: 
                                            <?php echo getTravelTime($row['pickup_location'], $row['delivery_location'], $row['moving_date'], $row['preferred_time']); ?>
                                        </span>
                                        <a href="https://waze.com/ul?q=<?php echo urlencode($row['delivery_location']); ?>&navigate=yes" target="_blank" class="text-[11px] text-blue-500 hover:text-blue-700 underline flex items-center gap-1">
                                            <i class="fab fa-waze"></i> Open Waze
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500"><?php echo htmlspecialchars($row['object_type']); ?></td>
                            <td class="px-6 py-4 text-xs">
                                <div class="font-bold text-gray-700"><?php echo date('M d', strtotime($row['moving_date'])); ?></div>
                                <div class="text-gray-400"><?php echo date('H:i', strtotime($row['preferred_time'])); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="chat.php?token=<?php echo $row['chat_token']; ?>" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-sm text-xs text-center">
                                    Contact & Quote
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden divide-y divide-gray-100">
                <?php foreach ($deliveries as $row): ?>
                <div class="p-5 space-y-4 bg-white hover:bg-gray-50 transition">
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] font-bold text-indigo-600 uppercase bg-indigo-50 px-2 py-1 rounded-md tracking-wider">
                            <?php echo htmlspecialchars($row['object_type'] ?? 'Delivery'); ?>
                        </span>
                        <div class="text-right">
                            <span class="block text-xs font-bold text-gray-700"><?php echo date('M d', strtotime($row['moving_date'])); ?></span>
                            <span class="block text-[10px] text-gray-400"><?php echo date('H:i', strtotime($row['preferred_time'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="space-y-2 relative">
                        <div class="flex items-start gap-3">
                            <div class="mt-1 flex flex-col items-center">
                                <i class="fas fa-circle text-[8px] text-green-500"></i>
                                <div class="w-0.5 h-6 bg-gray-200 my-1"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['pickup_location']); ?></span>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt text-sm text-indigo-500 mt-1"></i>
                            <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($row['delivery_location']); ?></span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <div class="flex items-center gap-2 text-[11px] text-orange-600 bg-orange-50 border border-orange-100 px-3 py-1.5 rounded-xl font-bold">
                            <i class="fas fa-clock"></i>
                            <span>Est. Trip: <?php echo getTravelTime($row['pickup_location'], $row['delivery_location'], $row['moving_date'], $row['preferred_time']); ?></span>
                        </div>
                        <a href="https://waze.com/ul?q=<?php echo urlencode($row['delivery_location']); ?>&navigate=yes" target="_blank" class="flex items-center gap-2 text-[11px] text-blue-600 bg-blue-50 border border-blue-100 px-3 py-1.5 rounded-xl font-bold">
                            <i class="fab fa-waze"></i>
                            <span>Open Waze</span>
                        </a>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <div class="flex flex-col">
                            <span class="text-[9px] text-gray-400 uppercase font-bold tracking-tighter">Customer</span>
                            <span class="text-xs font-bold text-gray-700"><?php echo htmlspecialchars($row['full_name']); ?></span>
                        </div>
                        <a href="chat.php?token=<?php echo $row['chat_token']; ?>" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-md active:scale-95 transition-transform">
                            Contact & Quote
                        </a>
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