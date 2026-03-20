<?php

require_once '../includes/Itaidbh.inc.php'; 

// Set session to last for 30 days
$timeout = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['is_driver'] != 1) {
    header("Location: driver_auth.php");
    exit();
}


// Helper function for Google Maps Travel Time
function getTravelTime($origin, $destination, $moveDate, $moveTime) {
    $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // Replace with your actual key
    $departureTimestamp = strtotime("$moveDate $moveTime");
    
    // Safety check: if departure is in the past, Google Matrix might fail
    if ($departureTimestamp < time()) {
        $departureTimestamp = time();
    }

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

// --- START OF SEARCH LOGIC ---
$whereClauses = ["(cs.is_active = 1 OR cs.is_active IS NULL)"];
$params = [];

// 1. Date Filter
if (!empty($_GET['search_date'])) {
    $whereClauses[] = "d.moving_date = :search_date";
    $params[':search_date'] = $_GET['search_date'];
}

// 2. Time Filter (Calculates a window of -15 to +15 minutes)
if (!empty($_GET['search_time'])) {
    $searchTime = $_GET['search_time'];
    $startTime = date('H:i:s', strtotime($searchTime . ' -15 minutes'));
    $endTime = date('H:i:s', strtotime($searchTime . ' +15 minutes'));
    
    $whereClauses[] = "d.preferred_time BETWEEN :start_time AND :end_time";
    $params[':start_time'] = $startTime;
    $params[':end_time'] = $endTime;
}

// 3. Region Filter (Searching within the pickup_location text)
if (!empty($_GET['region'])) {
    $whereClauses[] = "d.pickup_location LIKE :region";
    $params[':region'] = '%' . $_GET['region'] . '%';
}

$whereSql = implode(" AND ", $whereClauses);

try {
    $sql = "SELECT d.*, cs.chat_token 
            FROM deliveries d 
            LEFT JOIN chat_sessions cs ON d.id = cs.delivery_id 
            WHERE $whereSql
            ORDER BY d.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbError = $e->getMessage();
    $deliveries = [];
}
// --- END OF SEARCH LOGIC ---
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
                    <i class="fas fa-th-large w-5 text-indigo-400"></i> 
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

        <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 mb-10">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block ml-1">Date</label>
                    <input type="date" name="search_date" value="<?php echo htmlspecialchars($_GET['search_date'] ?? ''); ?>" 
                           class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block ml-1">Preferred Time</label>
                    <input type="time" name="search_time" value="<?php echo htmlspecialchars($_GET['search_time'] ?? ''); ?>"
                           class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 transition-all">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block ml-1">Region</label>
                    <select name="region" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 appearance-none transition-all">
                        <option value="">All Regions</option>
                        <option value="North" <?php echo ($_GET['region'] ?? '') == 'North' ? 'selected' : ''; ?>>צפון</option>
                        <option value="Center" <?php echo ($_GET['region'] ?? '') == 'Center' ? 'selected' : ''; ?>>מרכז</option>
                        <option value="South" <?php echo ($_GET['region'] ?? '') == 'South' ? 'selected' : ''; ?>>דרום</option>
                    </select>
                </div>
                <button type="submit" class="bg-indigo-600 text-white font-black text-xs uppercase tracking-widest py-4 px-6 rounded-2xl hover:bg-slate-900 transition-all shadow-xl shadow-indigo-100 active:scale-95">
                    <i class="fas fa-sliders mr-2"></i> Apply Filters
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            
            <div class="bg-indigo-600 p-6 rounded-[2rem] shadow-xl shadow-indigo-100 text-white relative overflow-hidden">
                <p class="text-indigo-200 text-xs font-black uppercase tracking-widest">Jobs Found</p>
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
                <?php if(!empty($_GET)): ?>
                    <a href="ItaiRegisteredDriver.php" class="text-xs font-black text-indigo-600 uppercase tracking-widest hover:underline">Clear Filters</a>
                <?php endif; ?>
            </div>

            <?php if (empty($deliveries)): ?>
                <div class="bg-white p-12 rounded-[2.5rem] text-center border-2 border-dashed border-slate-200">
                    <i class="fas fa-search text-4xl text-slate-300 mb-4"></i>
                    <p class="text-slate-500 font-bold">No jobs match your search criteria.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach ($deliveries as $job): 
                        $wazeUrl = "https://waze.com/ul?q=" . urlencode($job['pickup_location']) . "&navigate=yes";
                        $estDriveTime = getTravelTime($job['pickup_location'], $job['delivery_location'], $job['moving_date'], $job['preferred_time']);
                        
                        // Construct the link to the chat/bidding page using the token
                        $chatUrl = "chat.php?token=" . urlencode($job['chat_token']); 
                    ?>
                        <div class="bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-slate-50 overflow-hidden p-6 hover:shadow-lg transition-all duration-300">
                            
                            <div class="flex justify-between items-center mb-6">
                                <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                                    <?php echo htmlspecialchars($job['object_type'] ?? 'General'); ?>
                                </span>
                                <div class="flex items-center gap-2 text-slate-400">
                                    <i class="far fa-calendar text-xs"></i>
                                    <span class="text-xs font-bold uppercase tracking-tighter text-slate-600">
                                        <?php echo date('M d, Y', strtotime($job['moving_date'])); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="relative space-y-8 mb-8">
                                <div class="absolute left-[11px] top-3 w-[2px] h-[calc(100%-24px)] bg-slate-100"></div>
                                <div class="flex gap-4 items-start relative">
                                    <div class="w-6 h-6 rounded-full bg-white border-4 border-slate-100 shadow-sm flex items-center justify-center z-10">
                                        <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Pickup</p>
                                        <h3 class="text-base font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['pickup_location']); ?></h3>
                                        <a href="<?php echo $wazeUrl; ?>" target="_blank" class="inline-flex items-center gap-1.5 mt-2 text-indigo-600 text-[11px] font-black uppercase tracking-wider hover:opacity-70">
                                            <i class="fab fa-waze text-sm"></i> Navigate to Pickup
                                        </a>
                                    </div>
                                </div>
                                <div class="flex gap-4 items-start relative">
                                    <div class="w-6 h-6 rounded-full bg-white border-4 border-indigo-50 shadow-sm flex items-center justify-center z-10">
                                        <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest leading-none mb-1">Destination</p>
                                        <h3 class="text-base font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['delivery_location']); ?></h3>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 py-4 border-t border-slate-50 mb-6">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Est. Drive Time</p>
                                    <p class="text-sm font-extrabold text-slate-700"><?php echo $estDriveTime; ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Scheduled Time</p>
                                    <p class="text-sm font-extrabold text-slate-700"><?php echo date('H:i', strtotime($job['preferred_time'])); ?></p>
                                </div>
                            </div>

                            <a href="<?php echo $chatUrl; ?>" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-xs tracking-[0.2em] uppercase hover:bg-indigo-600 transition active:scale-95 shadow-xl shadow-slate-100 flex items-center justify-center gap-2">
                                Contact & Quote <i class="fas fa-chevron-right text-[10px]"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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