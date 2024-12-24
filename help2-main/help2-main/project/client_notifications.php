<?php
include('db_connect.php');
session_start();

// Client authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: login.php");
    exit;
}

// Fetch notifications
$sql = "SELECT n.*, j.title as job_title 
        FROM notifications n
        LEFT JOIN job_applications ja ON ja.application_id = 
            SUBSTRING_INDEX(SUBSTRING_INDEX(n.message, 'job: ', -1), ' ', 1)
        LEFT JOIN jobs j ON ja.job_id = j.id
        WHERE n.user_id = ? AND n.user_type = 'client'
        ORDER BY n.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result();

// Handle mark as read
if (isset($_GET['mark_read'])) {
    $update_sql = "UPDATE notifications SET is_read = 1 
                   WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ii', $_GET['mark_read'], $_SESSION['user_id']);
    $update_stmt->execute();
    header("Location: client_notifications.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a0a0a;
            --secondary: #1a1a1a;
            --accent: #2a2a2a;
            --gold: #d4af37;
            --silver: #c0c0c0;
            --text: #333;
        }

        body {
            background-color: var(--primary);
            font-family: 'Inter', -apple-system, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        /* Updated Navbar Styling */
        .navbar {
            background: var(--secondary) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            padding: 0.5rem 0;
        }

        /* Brand Logo Styling */
        .navbar-brand {
            color: var(--gold) !important;
            font-size: 1.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: 1px;
        }

        .navbar-brand i {
            font-size: 1.5rem;
        }

        /* Minimalistic Notifications Container */
        .notifications-container {
            max-width: 800px;
            margin: 2rem auto;
            background: var(--primary);
            border: 1px solid var(--gold);
            border-radius: 12px;
            padding: 2rem;
        }

        .page-title {
            color: var(--gold);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
        }

        /* Simplified Notification Card */
        .notification-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .notification-card:hover {
            border-color: var(--gold);
            transform: translateX(5px);
        }

        .notification-content {
            padding: 1.25rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            align-items: center;
        }

        /* Status Icon */
        .status-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 1rem;
            color: var(--gold);
            border: 1px solid var(--gold);
        }

        /* Notification Details */
        .notification-details {
            color: white;
        }

        .notification-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--gold);
            margin-bottom: 0.25rem;
        }

        .notification-message {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.25rem;
        }

        .notification-time {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Mark as Read Button */
        .mark-read-btn {
            color: var(--gold);
            background: transparent;
            border: 1px solid var(--gold);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .mark-read-btn:hover {
            background: var(--gold);
            color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(212, 175, 55, 0.5);
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="bi bi-diamond-fill me-2"></i>Kat
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="client_dashboard.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="client_profile.php">
                        <i class="fas fa-user-tie"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pending_applications.php">
                        <i class="fas fa-clipboard-list"></i> Applied Jobs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="client_notifications.php">
                        <i class="fas fa-bell"></i> Notifications
                    </a>
                </li>
                <li class="nav-item">
    <a class="nav-link" href="earnings.php">
        <i class="fas fa-wallet"></i> Earnings
    </a>
</li>
            </ul>
            <form class="d-flex me-3" action="search.php" method="GET">
                <input class="form-control me-2 search-input" type="search" placeholder="Search" name="query" aria-label="Search">
                <button class="btn btn-outline-light" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <a href="logout.php" class="btn btn-outline-light rounded-pill">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

    <div class="container">
        <div class="notifications-container">
            <h2 class="page-title">Notifications</h2>
            
            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-card">
                        <div class="notification-content">
                            <div class="status-icon">
                                <?php
                                $icon = 'bell';
                                if (strpos($notification['message'], 'reviewing') !== false) {
                                    $icon = 'clock';
                                } elseif (strpos($notification['message'], 'accepted') !== false) {
                                    $icon = 'check';
                                } elseif (strpos($notification['message'], 'rejected') !== false) {
                                    $icon = 'x';
                                }
                                ?>
                                <i class="fas fa-<?= $icon ?>"></i>
                            </div>
                            <div class="notification-details">
                                <div class="notification-title">
                                    <?= htmlspecialchars($notification['job_title']) ?>
                                </div>
                                <div class="notification-message">
                                    <?= str_replace('Your application has been', 'Application', $notification['message']) ?>
                                </div>
                                <div class="notification-time">
                                    <?= date('M j, g:i a', strtotime($notification['created_at'])) ?>
                                </div>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <a href="?mark_read=<?= $notification['id'] ?>" class="mark-read-btn">
                                    Mark as read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell"></i>
                    <h3>No new notifications</h3>
                    <p>You're all caught up!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>