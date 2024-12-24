<?php
include('db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$job_id = $_GET['job_id'] ?? null;
$freelancer_id = $_GET['freelancer_id'] ?? null;
$client_id = $_GET['client_id'] ?? null;

// Enhanced validation and redirection
if (!$job_id || (!$freelancer_id && !$client_id)) {
    header("Location: " . ($_SESSION['role'] == 'freelancer' ? 'my_job_posts.php' : 'pending_applications.php'));
    exit;
}

// Role-based access check
if ($_SESSION['role'] == 'freelancer' && !$client_id) {
    header("Location: my_job_posts.php");
    exit;
}
if ($_SESSION['role'] == 'client' && !$freelancer_id) {
    header("Location: pending_applications.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Determine the other user
if ($role == 'freelancer') {
    $other_user_id = $client_id;
} else {
    $other_user_id = $freelancer_id;
}

// Fetch job details
$job_sql = "SELECT j.*, u.first_name, u.surname 
            FROM jobs j
            LEFT JOIN users u ON j.freelancer_id = u.id 
            WHERE j.id = ?";
$job_stmt = $conn->prepare($job_sql);
$job_stmt->bind_param('i', $job_id);
$job_stmt->execute();
$job = $job_stmt->get_result()->fetch_assoc();

if (!$job) {
    header("Location: " . ($_SESSION['role'] == 'freelancer' ? 'my_job_posts.php' : 'pending_applications.php'));
    exit;
}

// Fetch other user details
$user_sql = "SELECT first_name, surname FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param('i', $other_user_id);
$user_stmt->execute();
$other_user = $user_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?= htmlspecialchars($job['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #1a1b1e;
            --bg-card: #25262b;
            --text-primary: #ffffff;
            --text-secondary: #909296;
            --accent-blue: #4dabf7;
            --accent-green: #51cf66;
            --accent-gold: #ffd700;
            --shadow: 0 8px 30px rgba(0,0,0,0.1);
        }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #2a2a2a 100%);
            color: var(--text-primary);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif;
        }

        .chat-container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 20px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chat-header {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px 20px 0 0;
        }

        .chat-header h4 {
            color: var(--accent-gold);
            margin: 0;
            font-weight: 600;
        }

        .chat-header p {
            color: var(--text-secondary);
            margin: 0.5rem 0 0;
            font-size: 0.9rem;
        }

        .messages-container {
            height: 600px;
            overflow-y: auto;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            scroll-behavior: smooth;
        }

        .messages-container::-webkit-scrollbar {
            width: 6px;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .message-bubble {
            max-width: 70%;
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            border-radius: 18px;
            position: relative;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-sent {
            background: linear-gradient(135deg, var(--accent-blue) 0%, #3b89ff 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
            box-shadow: 0 4px 15px rgba(59, 137, 255, 0.1);
        }

        .message-received {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            margin-right: auto;
            border-bottom-left-radius: 5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-input-container {
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0 0 20px 20px;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .message-input {
            flex-grow: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 0.75rem 1.25rem;
            color: var(--text-primary);
            outline: none;
            transition: all 0.3s ease;
        }

        .message-input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(77, 171, 247, 0.1);
        }

        .file-input-label {
            cursor: pointer;
            color: var(--text-secondary);
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            color: var(--accent-blue);
            background: rgba(77, 171, 247, 0.1);
        }

        .btn-primary {
            background: var(--accent-blue);
            border: none;
            width: 45px;
            height: 45px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #3b89ff;
            transform: scale(1.05);
        }

        .attachment-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-top: 0.75rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .back-button {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-gold);
            border-color: var(--accent-gold);
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0"><?= htmlspecialchars($job['title']) ?></h2>
                <p class="text-secondary">
                    Chat with <?= htmlspecialchars($other_user['first_name'] . ' ' . $other_user['surname']) ?>
                </p>
            </div>
            <a href="<?= $role == 'freelancer' ? 'my_job_posts.php' : 'pending_applications.php' ?>" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <h4><?= htmlspecialchars($job['title']) ?></h4>
                <p class="mb-0">Chatting with: <?= htmlspecialchars($other_user['first_name'] . ' ' . $other_user['surname']) ?></p>
            </div>

            <div id="chat-messages" class="messages-container">
                <!-- Messages will be loaded here -->
            </div>

            <form id="message-form" class="message-input-container">
                <input type="hidden" name="job_id" value="<?= $job_id ?>">
                <input type="hidden" name="sender_id" value="<?= $user_id ?>">
                <input type="hidden" name="receiver_id" value="<?= $other_user_id ?>">
                
                <label for="attachment" class="file-input-label">
                    <i class="fas fa-paperclip fa-lg"></i>
                </label>
                <input type="file" id="attachment" name="attachment" class="d-none">
                
                <input type="text" name="message" class="message-input" 
                       placeholder="Type a message..." autocomplete="off">
                
                <button type="submit" class="btn btn-primary rounded-circle">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadMessages();
            setInterval(loadMessages, 5000); // Refresh messages every 5 seconds
        });

        async function loadMessages() {
            const chatMessages = document.getElementById('chat-messages');
            const job_id = <?= $job_id ?>;
            const response = await fetch(`api/get_messages.php?job_id=${job_id}`);
            const messages = await response.json();

            chatMessages.innerHTML = '';
            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.classList.add('message-bubble', message.sender_id == <?= $user_id ?> ? 'message-sent' : 'message-received');
                messageDiv.innerHTML = `
                    <div>${message.message}</div>
                    <div class="message-time">
                        <span>${message.sender_name}</span>
                        <span>${new Date(message.created_at).toLocaleString()}</span>
                    </div>
                `;
                chatMessages.appendChild(messageDiv);
            });

            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        async function sendMessage(event) {
            event.preventDefault();
            const form = document.getElementById('message-form');
            const formData = new FormData(form);

            const response = await fetch('api/send_message.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                form.reset();
                loadMessages();
            } else {
                alert('Failed to send message');
            }
        }
    </script>
    <script src="js/chat.js"></script>
</body>
</html>