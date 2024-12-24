<?php
include('db_connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Define notification categories
$categories = [
    'application' => ['icon' => 'fa-paper-plane', 'color' => 'primary'],
    'review' => ['icon' => 'fa-star', 'color' => 'warning'],
    'acceptance' => ['icon' => 'fa-check-circle', 'color' => 'success'],
    'rejection' => ['icon' => 'fa-times-circle', 'color' => 'danger'],
    'message' => ['icon' => 'fa-envelope', 'color' => 'info']
];

// Group notifications by date
$grouped_notifications = [];
while ($notification = $result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($notification['created_at']));
    $grouped_notifications[$date][] = $notification;
}

if (isset($_GET['mark_read'])) {
    $notification_id = $_GET['mark_read'];
    $update_sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ii', $notification_id, $user_id);
    $update_stmt->execute();
    header("Location: notifications.php");
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
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/coreui@4.1.0/dist/css/coreui.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    :root {
        --sidebar-width: 280px;
        --sidebar-bg: #1c1c1e;
        --sidebar-hover: #2c2c2e;
        --card-bg: #ffffff;
        --text-primary: #000000;
        --text-secondary: #6e6e73;
        --accent-blue: #0071e3;
        --sidebar-border: #2c2c2e;
    }

    .wrapper {
        display: flex;
        min-height: 100vh;
        width: 100%;
    }

    .sidebar {
        background: var(--sidebar-bg);
        min-width: var(--sidebar-width);
        width: var(--sidebar-width);
        min-height: 100vh;
        position: fixed;
        padding: 1.5rem 1rem;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        z-index: 100;
    }

    .nav-link {
        color: #ffffff;
        font-size: 0.9rem;
        font-weight: 500;
        letter-spacing: -0.01em;
        padding: 0.9rem 1.25rem;
        margin: 0.25rem 0.75rem;
        border-radius: 8px;
        transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .nav-link:hover {
        background: var(--sidebar-hover);
        transform: translateX(4px);
    }

    .nav-link.active {
        background: var(--accent-blue);
        color: white;
        font-weight: 600;
    }

    .nav-link i {
        font-size: 1.1rem;
        opacity: 0.75;
    }

    .nav-link.text-danger {
        color: #dc2626;
    }

    .nav-link.text-danger:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .content {
        margin-left: var(--sidebar-width);
        padding: 2rem;
        width: calc(100% - var(--sidebar-width));
        background-color: #f8f9fa;
    }

    .notification-card {
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .notification-card .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .notification-card .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .notification-unread .card {
        background-color: #f0f7ff;
        border-left: 4px solid var(--accent-blue);
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }

    .date-header {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        padding: 1rem;
        margin: 1.5rem -1rem 1rem -1rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        z-index: 1;
    }

    .date-header h5 {
        color: #6c757d;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .mark-read-btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        border-radius: 20px;
        transition: all 0.2s ease;
    }

    .mark-read-btn:hover {
        background-color: var(--accent-blue);
        color: white;
        border-color: var(--accent-blue);
    }

    .notification-card.animate__fadeOutRight {
        animation-duration: 0.5s;
    }

    /* Custom colors for notification types */
    .bg-application { background-color: #0d6efd; }
    .bg-review { background-color: #ffc107; }
    .bg-acceptance { background-color: #198754; }
    .bg-rejection { background-color: #dc3545; }
    .bg-message { background-color: #0dcaf0; }

    /* Header styling */
    .notifications-header {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .notifications-header h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="sidebar">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="freelancer_dashboard.php">
                        <i class="cil-speedometer"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create_job.php">
                        <i class="cil-plus"></i> Create Job
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jobs.php">
                        <i class="cil-briefcase"></i> Job Listings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_applications.php">
                        <i class="cil-task"></i> My Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="edit_profile.php">
                        <i class="cil-user"></i> My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_job_posts.php">
                        <i class="cil-folder"></i> My Job Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="notifications.php">
                        <i class="cil-bell"></i> Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="cil-account-logout"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <div class="content">
            <div class="container">
                <div class="notifications-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2><i class="fas fa-bell me-2"></i> Notifications</h2>
                    </div>
                </div>

                <?php if (!empty($grouped_notifications)): ?>
                    <?php foreach ($grouped_notifications as $date => $notifications): ?>
                        <div class="date-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-day me-2"></i>
                                <?= date('F j, Y', strtotime($date)) ?>
                            </h5>
                        </div>

                        <?php foreach ($notifications as $notification): ?>
                            <?php 
                                $type = strpos(strtolower($notification['message']), 'accepted') !== false ? 'acceptance' :
                                       (strpos(strtolower($notification['message']), 'rejected') !== false ? 'rejection' :
                                       (strpos(strtolower($notification['message']), 'review') !== false ? 'review' : 'application'));
                            ?>
                            <div class="notification-card animate__animated animate__fadeIn <?= !$notification['is_read'] ? 'notification-unread' : '' ?>">
                                <div class="card">
                                    <div class="card-body d-flex align-items-center">
                                        <div class="notification-icon bg-<?= $type ?>">
                                            <i class="fas <?= $categories[$type]['icon'] ?> text-white"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('g:i a', strtotime($notification['created_at'])) ?>
                                            </small>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="?mark_read=<?= $notification['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary ms-3 mark-read-btn">
                                                <i class="fas fa-check me-1"></i> Mark as read
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No notifications found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const card = this.closest('.notification-card');
                card.classList.add('animate__fadeOutRight');
                setTimeout(() => {
                    window.location = this.getAttribute('href');
                }, 500);
            });
        });
    </script>
</body>
</html>