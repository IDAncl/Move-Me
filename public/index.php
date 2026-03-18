<?php 
    require_once '../includes/Itaidbh.inc.php';
    session_start();

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_role'] = 'guest'; 
        $_SESSION['user_name'] = 'Guest_' . substr(uniqid(), -4);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MoveMe - Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .gradient-bg { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); }
        
        /* Mobile Input Fixes */
        input, select, textarea { font-size: 16px !important; } /* Prevents iOS zoom on focus */
        
        .floating-label-input:focus-within label { color: #4f46e5; }
        .custom-shadow { box-shadow: 0 20px 50px rgba(79, 70, 229, 0.1); }
        
        @keyframes subtle-float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }
        .float-icon { animation: subtle-float 3s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen">

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="bg-indigo-600 p-1.5 rounded-lg shadow-lg shadow-indigo-200">
                    <i data-lucide="truck" class="w-5 h-5 text-white"></i>
                </div>
                <span class="text-xl font-extrabold tracking-tighter text-slate-900">MoveMe</span>
            </div>
            <div class="md:hidden bg-slate-100 p-2 rounded-xl">
                <i data-lucide="menu" class="w-5 h-5 text-slate-600"></i>
            </div>
            <nav class="hidden md:flex space-x-8 text-sm font-bold text-slate-500">
                <a href="#" class="hover:text-indigo-600 transition">How it Works</a>
                <a href="ItaiForDrivers.php" class="hover:text-indigo-600 transition">For Drivers</a>
            </nav>
        </div>
    </header>

    <div class="gradient-bg pt-16 pb-32 px-6">
        <div class="max-w-4xl mx-auto text-center text-white">
            <div class="inline-flex items-center gap-2 bg-white/10 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 border border-white/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Available in your area
            </div>
            <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight leading-tight">Move Anything,<br class="md:hidden"> Anywhere.</h1>
            <p class="text-indigo-100 text-lg font-medium opacity-90 max-w-xl mx-auto">Skip the stress. Verified drivers are ready to bid on your move right now.</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 -mt-20 pb-20">
        <div class="glass-effect rounded-[2.5rem] custom-shadow p-6 md:p-10 border border-white">
            <form id="bookingForm" action="../includes/ItaiSendNewDeliveryRequest.php" method="POST" class="space-y-8">
                
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i data-lucide="map" class="w-4 h-4"></i>
                    </div>
                    <h2 class="font-black text-slate-800 uppercase text-[11px] tracking-widest">Route Details</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    <div class="space-y-1.5 floating-label-input">
                        <label class="block text-[11px] font-black text-slate-400 uppercase ml-1">Pickup Location</label>
                        <div class="relative group">
                            <i data-lucide="map-pin" class="absolute left-4 top-4 w-5 h-5 text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                            <input type="text" id="pickup" name="pickup" placeholder="Building, Street, City" required
                                class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                        </div>
                    </div>

                    <div class="space-y-1.5 floating-label-input">
                        <label class="block text-[11px] font-black text-slate-400 uppercase ml-1">Delivery Destination</label>
                        <div class="relative group">
                            <i data-lucide="navigation" class="absolute left-4 top-4 w-5 h-5 text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                            <input type="text" id="delivery" name="delivery" placeholder="Where is it going?" required
                                class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                        </div>
                    </div>
                </div>

                <hr class="border-slate-50">

                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i data-lucide="box" class="w-4 h-4"></i>
                    </div>
                    <h2 class="font-black text-slate-800 uppercase text-[11px] tracking-widest">Logistics & Item</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-slate-400 uppercase ml-1">Date</label>
                        <input type="date" id="moveDate" name="moveDate" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-slate-400 uppercase ml-1">Time</label>
                        <input type="time" id="moveTime" name="moveTime" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-slate-400 uppercase ml-1">Item Type</label>
                        <select id="objectType" name="objectType" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700 appearance-none">
                            <option value="">Select type...</option>
                            <option value="furniture">Furniture</option>
                            <option value="appliances">Appliances</option>
                            <option value="boxes">Boxes</option>
                            <option value="vehicle">Vehicle</option>
                            <option value="piano">Piano/Heavy</option>
                        </select>
                    </div>
                </div>

                <div class="p-6 bg-indigo-600 rounded-[2rem] text-white shadow-xl shadow-indigo-200">
                    <div class="grid md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-indigo-200 uppercase tracking-widest">Your Full Name</label>
                            <input type="text" id="fullName" name="fullName" placeholder="John Doe" required
                                class="w-full px-5 py-4 bg-white/10 border border-white/20 rounded-xl focus:bg-white focus:text-slate-900 outline-none transition-all font-bold text-white placeholder:text-indigo-200">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-indigo-200 uppercase tracking-widest">Phone Number</label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="050-000-0000" required
                                class="w-full px-5 py-4 bg-white/10 border border-white/20 rounded-xl focus:bg-white focus:text-slate-900 outline-none transition-all font-bold text-white placeholder:text-indigo-200">
                        </div>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full bg-slate-900 text-white py-5 rounded-[2rem] font-black text-sm tracking-[0.2em] uppercase hover:bg-indigo-600 transition-all shadow-xl active:scale-95 flex items-center justify-center gap-3">
                    FIND MY DRIVER <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('moveDate').setAttribute('min', today);
    </script>
</body>
</html>