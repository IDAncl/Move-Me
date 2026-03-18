<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

$token = $_GET['token'] ?? '';
$userName = $_SESSION['user_name'] ?? 'guest';
$userRole = $_SESSION['user_role'] ?? 'driver';


// 1. Verify session and get delivery details
$stmt = $pdo->prepare("SELECT d.pickup_location, d.delivery_location, cs.is_active, cs.chosen_driver_id 
                       FROM chat_sessions cs 
                       JOIN deliveries d ON cs.delivery_id = d.id 
                       WHERE cs.chat_token = ?");
$stmt->execute([$token]);
$session = $stmt->fetch();

if (!$session) {
    die("Invalid chat session.");
}

// 1. Verify session exists
if (!$session) {
    die("Error: Chat session not found for this token.");
}

// 2. Redirect ONLY if the session is explicitly marked as inactive AND has a winner
// Use (int) to ensure we are comparing numbers correctly
if ((int)$session['is_active'] === 0 && !empty($session['chosen_driver_id'])) {
    echo "<script>
        alert('This job has been assigned to a driver. The chat is now closed.'); 
        window.location.href='ItaiRegisteredDriver.php';
    </script>";
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
<body class="bg-gray-100 h-screen flex flex-col">

    <div class="bg-indigo-600 text-white p-4 shadow-md flex justify-between items-center">
        <div>
            <h1 class="font-bold text-lg">Delivery Bidding Chat</h1>
            <p class="text-xs opacity-80"><?php echo htmlspecialchars($session['pickup_location'] . " → " . $session['delivery_location']); ?></p>
        </div>
        <?php if ($userRole === 'driver'): ?>
        <a href="ItaiRegisteredDriver.php" class="text-white opacity-80 hover:opacity-100">
                <i class="fas fa-times text-xl"></i>
            </a>
        <?php else: ?>
            <a href="index.php" class="text-white opacity-80 hover:opacity-100">
                <i class="fas fa-times text-xl"></i>
            </a>
        <?php endif; ?>
    </div>

    <div id="chat-box" class="flex-grow overflow-y-auto p-4 space-y-4 no-scrollbar">
        </div>

    <div class="bg-white p-4 border-t shadow-lg">
        <form id="chat-form" class="max-w-4xl mx-auto">
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" id="sender" value="<?php echo htmlspecialchars($userName); ?>">
            
            <div class="flex flex-col gap-3">
                <?php if ($userRole === 'driver'): ?>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-400 uppercase">Your Quote:</span>
                    <input type="number" id="quote_price" placeholder="שקל" 
                           class="w-24 border rounded-lg px-3 py-1 text-sm focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <?php endif; ?>

                <div class="flex gap-2">
                    <input type="text" id="message" placeholder="Type a message..." 
                           class="flex-grow border rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="submit" class="bg-indigo-600 text-white w-12 h-10 rounded-full flex items-center justify-center shadow-md active:scale-95 transition">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const chatForm = document.getElementById('chat-form');
        const token = document.getElementById('token').value;
        const userRole = '<?php echo $userRole; ?>';

    
        const userRole = '<?php echo $userRole; ?>';
        const currentSessionName = '<?php echo $userName; ?>'; // Add this
        console.log("Debug - Role:", userRole, "Name:", currentSessionName);

        async function loadMessages() {
            const res = await fetch(`get_messages.php?token=${token}`);
            const messages = await res.json();
            
            chatBox.innerHTML = messages.map(msg => {
                const isMe = msg.sender_name === '<?php echo $userName; ?>';
                
                // Quote UI
                let quoteHtml = '';
                if (msg.quote_price) {
                    quoteHtml = `
                        <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="text-yellow-700 font-bold text-sm">Offer: $${msg.quote_price}</div>
                            ${userRole === 'customer' ? `
                                <button onclick="acceptOffer('${msg.sender_name}')" 
                                        class="mt-2 w-full bg-green-600 text-white py-1 px-3 rounded text-xs font-bold hover:bg-green-700 transition">
                                    Accept This Driver
                                </button>
                            ` : ''}
                        </div>`;
                }

                return `
                    <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'}">
                        <span class="text-[10px] text-gray-500 mb-1 px-2">${msg.sender_name}</span>
                        <div class="max-w-[85%] px-4 py-2 rounded-2xl shadow-sm text-sm 
                            ${isMe ? 'bg-indigo-600 text-white rounded-tr-none' : 'bg-white text-gray-800 rounded-tl-none border'}">
                            ${msg.message}
                            ${quoteHtml}
                        </div>
                    </div>
                `;
            }).join('');
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        chatForm.onsubmit = async (e) => {
            e.preventDefault();
            const messageInput = document.getElementById('message');
            const quoteInput = document.getElementById('quote_price');
            
            const messageValue = messageInput.value.trim();
            const quoteValue = quoteInput ? quoteInput.value.trim() : '';

            // Check if BOTH are empty. If so, don't send anything.
            if (!messageValue && !quoteValue) {
                alert("Please enter a message or a price quote.");
                return;
            }

            const formData = new FormData();
            formData.append('token', token);
            formData.append('sender', document.getElementById('sender').value);
            formData.append('message', messageValue); // Can now be empty
            
            if (quoteInput && quoteValue) {
                formData.append('quote_price', quoteValue);
            }

            try {
                const response = await fetch('send_message.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.status === 'success') {
                    messageInput.value = '';
                    if (quoteInput) quoteInput.value = '';
                    loadMessages();
                }
            } catch (error) {
                console.error("Error sending message:", error);
            }
        };

        async function acceptOffer(driverName) {
            if (!confirm(`Are you sure you want to hire ${driverName}? This will close the bidding.`)) return;

            const formData = new FormData();
            formData.append('token', token);
            formData.append('driver_name', driverName);

            const res = await fetch('accept_offer.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.status === 'success') {
                alert('Driver Hired! Chat is now closing.');
                window.location.href = 'ItaiRegisteredDriver.php';
            }
        }

        setInterval(loadMessages, 3000);
        loadMessages();
    </script>
</body>
</html>