<?php 

    require_once '../includes/Itaidbh.inc.php';
    session_start();

    // If the user isn't logged in at all, they are a guest
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_role'] = 'guest'; 
        $_SESSION['user_name'] = 'Guest_' . substr(uniqid(), -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoveMe - Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .driver-card {
            transition: all 0.3s ease;
        }
        .driver-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2">
                    <i data-lucide="truck" class="w-8 h-8 text-indigo-600"></i>
                    <span class="text-2xl font-bold text-gray-900">MoveMe</span>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium">How it Works</a>
                    <a href="http://localhost/itaiTalStartup/public/ItaiForDrivers.php" class="text-gray-600 hover:text-indigo-600 font-medium">For Drivers</a>
                    <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium">Support</a>
                </nav>
                <!-- <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition">
                    Sign In
                </button> -->
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <div class="gradient-bg py-20 px-4">
        <div class="max-w-4xl mx-auto text-center text-white mb-12">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">Move Anything, Anywhere</h1>
            <p class="text-xl md:text-2xl opacity-90">Get instant offers from verified drivers in your area</p>
        </div>

            <div class="max-w-4xl mx-auto">
        <div class="glass-effect rounded-2xl shadow-2xl p-6 md:p-8">
            <form id="bookingForm" action="../includes/ItaiSendNewDeliveryRequest.php" method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Pickup Location</label>
                        <div class="relative">
                            <i data-lucide="map-pin" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                            <input type="text" id="pickup" name="pickup" placeholder="Enter pickup address" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Delivery Location</label>
                        <div class="relative">
                            <i data-lucide="navigation" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                            <input type="text" id="delivery" name="delivery" placeholder="Enter delivery address" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Moving Date</label>
                        <div class="relative">
                            <i data-lucide="calendar" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                            <input type="date" id="moveDate" name="moveDate" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Preferred Time</label>
                        <div class="relative">
                            <i data-lucide="clock" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                            <input type="time" id="moveTime" name="moveTime" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">What are you moving?</label>
                        <div class="relative">
                            <i data-lucide="package" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                            <select id="objectType" name="objectType" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition bg-white">
                                <option value="">Select item type...</option>
                                <option value="furniture">Furniture</option>
                                <option value="appliances">Appliances</option>
                                <option value="boxes">Boxes & Packages</option>
                                <option value="vehicle">Vehicle / Motorcycle</option>
                                <option value="piano">Piano / Heavy Items</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">Additional Details</label>
                    <textarea id="description" name="description" rows="3" placeholder="Describe dimensions, weight, or special handling..."
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Full Name</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                            <input type="text" id="fullName" name="fullName" placeholder="Enter your full name" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-gray-700">Phone Number</label>
                        <div class="relative">
                            <i data-lucide="phone" class="absolute left-3 top-3 w-5 h-5 text-gray-400"></i>
                            <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="05X-XXXXXXX" required
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition">
                        </div>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full bg-indigo-600 text-white py-4 rounded-lg font-bold text-lg hover:bg-indigo-700 transform hover:scale-[1.02] transition-all shadow-lg flex items-center justify-center space-x-2">
                    <span>Get Offers Now</span>
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Loading Section (Hidden by default) -->
    <div id="loadingSection" class="hidden max-w-4xl mx-auto py-20 text-center">
        <div class="inline-block p-4 rounded-full bg-indigo-100 mb-4">
            <i data-lucide="truck" class="w-12 h-12 text-indigo-600 loading-spinner"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Finding available drivers...</h2>
        <p class="text-gray-600">We're matching you with drivers in your area</p>
    </div>

    <!-- Results Section (Hidden by default) -->
    <div id="resultsSection" class="hidden max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 space-y-4 md:space-y-0">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Available Drivers</h2>
                <p class="text-gray-600 mt-1">Found <span id="offerCount" class="font-semibold text-indigo-600">5</span> offers for your move</p>
            </div>
            
            <div class="flex space-x-4">
                <select id="sortBy" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="rating">Highest Rated</option>
                </select>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-8">
            <button class="filter-btn active px-4 py-2 rounded-full bg-indigo-600 text-white text-sm font-medium transition" data-filter="all">
                All Vehicles
            </button>
            <button class="filter-btn px-4 py-2 rounded-full bg-white text-gray-700 border border-gray-300 text-sm font-medium hover:bg-gray-50 transition" data-filter="van">
                Van
            </button>
            <button class="filter-btn px-4 py-2 rounded-full bg-white text-gray-700 border border-gray-300 text-sm font-medium hover:bg-gray-50 transition" data-filter="truck">
                Truck
            </button>
            <button class="filter-btn px-4 py-2 rounded-full bg-white text-gray-700 border border-gray-300 text-sm font-medium hover:bg-gray-50 transition" data-filter="pickup">
                Pickup
            </button>
        </div>

        <!-- Driver Cards Grid -->
        <div id="driversGrid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Cards will be inserted here by JavaScript -->
        </div>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl transform transition-all">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900">Confirm Booking</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <div id="modalContent" class="space-y-4">
                <!-- Content inserted by JS -->
            </div>
            
            <div class="mt-6 space-y-3">
                <button onclick="confirmBooking()" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
                    Confirm & Pay
                </button>
                <button onclick="closeModal()" class="w-full bg-gray-100 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <div id="successMessage" class="hidden fixed bottom-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 z-50">
        <i data-lucide="check-circle" class="w-6 h-6"></i>
        <div>
            <p class="font-bold">Booking Confirmed!</p>
            <p class="text-sm opacity-90">The driver will contact you shortly.</p>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        lucide.createIcons();
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('moveDate').setAttribute('min', today);
    </script>
</body>
</html>