<?php
require_once '../includes/Itaiconfig.php';
// This generates the actual link that opens the Google Login window
$login_url = $client->createAuthUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Sign-In | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="text-gray-800 antialiased">

    <nav class="flex items-center justify-between px-6 md:px-20 py-6 text-white">
        <div class="flex items-center gap-2 text-2xl font-bold tracking-tight">
            <i class="fas fa-truck-moving"></i> MoveMe
        </div>
        <div class="hidden md:flex gap-10 font-medium">
            <a href="#" class="hover:text-indigo-200 transition">How it Works</a>
            <a href="#" class="text-indigo-200 border-b-2 border-indigo-200">For Drivers</a>
            <a href="#" class="hover:text-indigo-200 transition">Support</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            
            <div class="bg-white p-10 rounded-2xl shadow-2xl">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Welcome, Driver</h2>
                <p class="text-gray-500 mb-8">Sign in to access your dashboard, find jobs, and manage your fleet.</p>
                
                <div class="space-y-4">
                    <a href="<?php echo $login_url; ?>" class="w-full py-4 px-6 border border-gray-300 rounded-xl flex items-center justify-center gap-4 text-lg font-semibold hover:bg-gray-50 transition-all shadow-sm">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="24" alt="Google">
                        Sign in with Google
                    </a>

                    <div class="flex items-center my-6">
                        <hr class="flex-grow border-gray-100">
                        <span class="px-4 text-gray-400 text-sm uppercase tracking-widest">Driver Portal</span>
                        <hr class="flex-grow border-gray-100">
                    </div>

                    <p class="text-center text-sm text-gray-400 px-8">
                        By signing in, you agree to MoveMe's 
                        <a href="#" class="text-indigo-600 underline">Terms of Service</a> and 
                        <a href="#" class="text-indigo-600 underline">Privacy Policy</a>.
                    </p>
                </div>
            </div>

            <div class="text-white">
                <h1 class="text-5xl md:text-7xl font-black leading-tight mb-6">
                    Drive with <br><span class="text-indigo-200">MoveMe.</span>
                </h1>
                <p class="text-xl text-indigo-100 mb-8 leading-relaxed">
                    Join thousands of professional drivers. Get the best rates, 24/7 support, and the freedom to choose your own routes.
                </p>
                <div class="flex gap-4">
                    <div class="flex -space-x-2">
                        <img class="w-10 h-10 rounded-full border-2 border-indigo-500" src="https://i.pravatar.cc/100?u=1" alt="">
                        <img class="w-10 h-10 rounded-full border-2 border-indigo-500" src="https://i.pravatar.cc/100?u=2" alt="">
                        <img class="w-10 h-10 rounded-full border-2 border-indigo-500" src="https://i.pravatar.cc/100?u=3" alt="">
                    </div>
                    <p class="text-sm self-center font-medium">+400 drivers joined this week</p>
                </div>
            </div>
        </div>

        <div class="mt-24 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-2xl text-white card-hover">
                <div class="w-12 h-12 bg-indigo-500 rounded-lg flex items-center justify-center mb-6 shadow-lg">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Guaranteed Pay</h3>
                <p class="text-indigo-100 opacity-80">We offer the highest industry rates. Get paid instantly after every successfully completed move.</p>
            </div>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-2xl text-white card-hover">
                <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center mb-6 shadow-lg">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Total Flexibility</h3>
                <p class="text-indigo-100 opacity-80">Be your own boss. Choose the jobs that fit your schedule and your vehicle's capacity.</p>
            </div>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-2xl text-white card-hover">
                <div class="w-12 h-12 bg-pink-500 rounded-lg flex items-center justify-center mb-6 shadow-lg">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Premium Insurance</h3>
                <p class="text-indigo-100 opacity-80">Every load is insured. Drive with peace of mind knowing we've got your back on the road.</p>
            </div>
        </div>
    </main>

    <footer class="py-20 text-center text-indigo-300 text-sm">
        &copy; 2026 MoveMe Logistics. For Drivers, By Drivers.
    </footer>

</body>
</html>