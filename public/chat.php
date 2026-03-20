<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

// 1. Get Token and Session Data
$token = $_GET['token'] ?? '';
$userName = $_SESSION['user_name'] ?? 'Guest';

// Fix: Check both possible session keys for the role
$isDriverFlag = (isset($_SESSION['is_driver']) && $_SESSION['is_driver'] == 1);
$userRole = $isDriverFlag ? 'driver' : 'customer';

// 2. Database Verification with detailed error handling
if (empty($token)) {
    die("Error: No chat token provided in the URL.");
}

$stmt = $pdo->prepare("
    SELECT d.pickup_location, d.delivery_location, cs.is_active, cs.chosen_driver_id 
    FROM chat_sessions cs 
    JOIN deliveries d ON cs.delivery_id = d.id 
    WHERE cs.chat_token = ?
");
$stmt->execute([$token]);
$session = $stmt->fetch();

if (!$session) {
    // Debug: Check if the token exists but the delivery is missing
    $check = $pdo->prepare("SELECT id FROM chat_sessions WHERE chat_token = ?");
    $check->execute([$token]);
    if ($check->fetch()) {
        die("Error: Chat exists, but the linked delivery record was not found.");
    }
    die("Error: This chat link is no longer valid or does not exist in our system.");
}

// 3. Handle closed sessions
if ((int)$session['is_active'] === 0 && !empty($session['chosen_driver_id'])) {
    echo "<script>alert('This job has been assigned. The chat is now closed.'); window.location.href='index.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>.no-scrollbar::-webkit-scrollbar { display: none; }</style>
</head>
<body class="bg-gray-50 h-screen flex flex-col font-sans">

    <div class="bg-indigo-600 text-white p-4 shadow-lg flex justify-between items-center sticky top-0 z-10">
        <div>
            <h1 class="font-bold text-lg leading-tight">Delivery Chat</h1>
            <p class="text-[10px] opacity-90 uppercase tracking-widest font-bold">
                <?php echo htmlspecialchars($session['pickup_location'] . " → " . $session['delivery_location']); ?>
            </p>
        </div>
        <a href="<?php echo ($userRole === 'driver') ? 'ItaiRegisteredDriver.php' : 'index.php'; ?>" class="hover:bg-white/20 p-2 rounded-full transition">
            <i class="fas fa-times text-xl"></i>
        </a>
    </div>

    <div id="chat-box" class="flex-grow overflow-y-auto p-4 space-y-6 no-scrollbar"></div>

    <div class="bg-white p-4 border-t border-gray-100 shadow-[0_-4px_10px_rgba(0,0,0,0.03)]">
        <form id="chat-form" class="max-w-4xl mx-auto flex flex-col gap-3">
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" id="sender" value="<?php echo htmlspecialchars($userName); ?>">
            
            <?php if ($userRole === 'driver'): ?>
            <div class="flex items-center gap-2 bg-indigo-50/50 p-2 rounded-xl border border-indigo-100">
                <span class="text-[10px] font-black text-indigo-500 pl-2 uppercase">Your Price</span>
                <input type="number" id="quote_price" placeholder="₪ 0.00" 
                       class="w-full bg-transparent font-bold text-indigo-700 focus:outline-none placeholder:text-indigo-300">
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 bg-gray-100 rounded-full p-1 pl-4 pr-1 focus-within:ring-2 focus-within:ring-indigo-500 transition-all">
                <input type="text" id="message" placeholder="Type a message..." 
                       class="flex-grow bg-transparent py-2 text-sm focus:outline-none">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-md active:scale-90 transition">
                    <i class="fas fa-paper-plane text-xs"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const chatForm = document.getElementById('chat-form');
        const token = document.getElementById('token').value;
        const userRole = '<?php echo $userRole; ?>';
        const currentUserName = '<?php echo $userName; ?>';

        async function loadMessages() {
            try {
                const res = await fetch(`get_messages.php?token=${token}`);
                const messages = await res.json();
                
                chatBox.innerHTML = messages.map(msg => {
                    if (msg.sender_name === 'System') {
                        return `<div class="flex justify-center my-6">
                                    <div class="bg-indigo-50 border border-indigo-100 text-indigo-700 px-6 py-2 rounded-full text-[11px] font-black uppercase tracking-wider shadow-sm flex items-center gap-2">
                                        <i class="fas fa-check-circle"></i> ${msg.message}
                                    </div>
                                </div>`;
                    }

                    const isMe = msg.sender_name === currentUserName;
                    let quoteHtml = '';
                    if (msg.quote_price) {
                        quoteHtml = `<div class="mt-3 overflow-hidden rounded-xl border border-emerald-100 bg-white shadow-sm">
                                        <div class="bg-emerald-50 px-3 py-1.5 text-[10px] font-bold uppercase text-emerald-700">Driver Offer</div>
                                        <div class="p-3 text-center">
                                            <div class="text-xl font-black text-gray-900">₪${msg.quote_price}</div>
                                            ${userRole === 'customer' ? `<button onclick="acceptOffer('${msg.sender_name}', ${msg.quote_price})" class="mt-2 w-full rounded-lg bg-emerald-600 py-2 text-xs font-bold text-white hover:bg-emerald-700 active:scale-95 transition">Accept This Driver</button>` : '<p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-tighter">Awaiting customer...</p>'}
                                        </div>
                                    </div>`;
                    }

                    const text = msg.message ? msg.message.trim() : '';
                    const bubble = text !== '' ? `<div class="max-w-[85%] px-4 py-2.5 shadow-sm text-sm leading-relaxed ${isMe ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 rounded-2xl rounded-tl-none border border-gray-200'}">${msg.message}</div>` : '';

                    return `<div class="flex flex-col mb-4 ${isMe ? 'items-end' : 'items-start'}">
                                <span class="text-[10px] text-gray-400 mb-1 px-2 font-bold uppercase tracking-tighter">${isMe ? 'You' : msg.sender_name}</span>
                                ${bubble}
                                <div class="w-48">${quoteHtml}</div>
                            </div>`;
                }).join('');
                
                chatBox.scrollTop = chatBox.scrollHeight;
            } catch (e) { console.error("Error refreshing chat:", e); }
        }

        chatForm.onsubmit = async (e) => {
            e.preventDefault();
            const messageInput = document.getElementById('message');
            const quoteInput = document.getElementById('quote_price');
            const messageValue = messageInput.value.trim();
            const quoteValue = quoteInput ? quoteInput.value.trim() : '';

            if (!messageValue && !quoteValue) return;

            const formData = new FormData();
            formData.append('token', token);
            formData.append('sender', currentUserName);
            formData.append('message', messageValue);
            if (quoteValue) formData.append('quote_price', quoteValue);

            const response = await fetch('send_message.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.status === 'success') {
                messageInput.value = '';
                if (quoteInput) quoteInput.value = '';
                loadMessages();
            }
        };

        async function acceptOffer(driverName, price) {
            if (!confirm(`Hire ${driverName} for ₪${price}? This closes the bidding.`)) return;
            const formData = new FormData();
            formData.append('token', token);
            formData.append('driver_name', driverName);

            const res = await fetch('accept_offer.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.status === 'success') {
                window.location.href = `checkout.php?token=${token}&amount=${price}`;
            }
        }

        setInterval(loadMessages, 3000);
        loadMessages();
    </script>
</body>
</html>