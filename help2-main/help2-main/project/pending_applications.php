<?php
include('db_connect.php');
session_start();

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: login.php");
    exit;
}

// Handle application cancellation
if (isset($_POST['cancel_application'])) {
    $app_id = $_POST['application_id'];
    $cancel_sql = "DELETE FROM job_applications 
                   WHERE application_id = ? AND client_id = ? 
                   AND status = 'pending'";
    $cancel_stmt = $conn->prepare($cancel_sql);
    $cancel_stmt->bind_param("ii", $app_id, $_SESSION['user_id']);
    $cancel_stmt->execute();
}

// Update the SQL query to show all applications
$sql = "SELECT ja.*, 
        j.title, j.description, j.reward, j.job_type,
        j.start_date, j.end_date, j.job_image, j.assigned_to,
        j.freelancer_id, j.status as job_status,
        u.email as freelancer_email, 
        u.first_name as freelancer_first_name,
        u.surname as freelancer_surname
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id 
        JOIN users u ON j.freelancer_id = u.id
        WHERE ja.client_id = ?
        ORDER BY ja.applied_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result();

// Notification SQL (kept for reference)
$notify_sql = "INSERT INTO notifications (user_id, message, user_type) 
               SELECT freelancer_id,
                      CONCAT('New application received for job: ', title),
                      'freelancer'
               FROM jobs WHERE id = ?";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Freelancing Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
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

        /* Navbar Styling (matching client_dashboard) */
        .navbar {
            background: var(--secondary) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .navbar-brand {
            color: var(--gold) !important;
            font-size: 1.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Clean Application Cards */
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .application-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .application-card:hover {
            transform: translateY(-3px);
            border-color: var(--gold);
        }

        /* Status Header */
        .status-banner {
            background: var(--secondary);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            padding-right: 4rem; /* Make space for thumbnail */
        }

        .status-icon {
            color: var(--gold);
            font-size: 1.1rem;
        }

        .status-text {
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Card Content */
        .card-body {
            padding: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .job-title {
            color: var(--gold);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 6px;
        }

        .info-label {
            color: var(--gold);
            font-size: 0.85rem;
        }

        .info-value {
            color: white;
            font-size: 0.9rem;
        }

        /* Response Section */
        .response-section {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(212, 175, 55, 0.05);
            border-radius: 8px;
            border-left: 2px solid var(--gold);
        }

        .response-title {
            color: var(--gold);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        /* Action Buttons */
        .card-actions {
            margin-top: 1.5rem;
            display: grid;
            gap: 1rem;
        }

        .btn-action {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-chat {
            background: var(--gold);
            color: var(--primary);
            border: none;
        }

        .btn-chat:hover {
            background: #c4a032;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid var(--gold);
            color: var(--gold);
        }

        .btn-cancel:hover {
            background: rgba(212, 175, 55, 0.1);
        }

        /* Updated Heading Style */
        .page-title {
            color: white;
            font-weight: 800;
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }

        /* Job Thumbnail Style */
        .job-thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            margin-left: auto;
            border: 2px solid var(--gold);
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--secondary);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-diamond-fill me-2"></i>Kat
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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

    <div class="container py-5">
        <h2 class="page-title">My Job Applications</h2>
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Application cancelled successfully
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="applications-grid">
            <?php if($applications->num_rows > 0): ?>
                <?php while($app = $applications->fetch_assoc()): ?>
                    <div class="application-card">
                        <div class="status-banner">
                            <i class="fas fa-<?= 
                                $app['status'] === 'accepted' ? 'check-circle' : 
                                ($app['status'] === 'reviewing' ? 'clock' : 
                                ($app['status'] === 'rejected' ? 'times-circle' : 'paper-plane')) 
                            ?> status-icon"></i>
                            <span class="status-text"><?= ucfirst($app['status']) ?> Application</span>
                            
                            <!-- Add small job image thumbnail -->
                            <?php if(!empty($app['job_image'])): ?>
                                <img src="<?= htmlspecialchars($app['job_image']) ?>" 
                                     alt="Job Thumbnail" 
                                     class="job-thumbnail"
                                     onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <h3 class="job-title"><?= htmlspecialchars($app['title']) ?></h3>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Budget</span>
                                    <span class="info-value">₹<?= number_format($app['reward'], 2) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Job Type</span>
                                    <span class="info-value"><?= htmlspecialchars($app['job_type']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Applied On</span>
                                    <span class="info-value"><?= date('M d, Y', strtotime($app['applied_date'])) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Posted By</span>
                                    <span class="info-value"><?= htmlspecialchars($app['freelancer_first_name']) ?></span>
                                </div>
                            </div>

                            <?php if(!empty($app['freelancer_response'])): ?>
                                <div class="response-section">
                                    <div class="response-title">Freelancer Response</div>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($app['freelancer_response'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="card-actions">
                                <?php if($app['status'] == 'accepted'): ?>
                                    <a href="chat.php?job_id=<?= $app['job_id'] ?>&freelancer_id=<?= $app['freelancer_id'] ?>" 
                                       class="btn btn-action btn-chat">
                                        <i class="fas fa-comments"></i> Chat with Freelancer
                                    </a>
                                <?php endif; ?>

                                <?php if($app['status'] == 'pending'): ?>
                                    <form method="POST" action="" style="display: contents;">
                                        <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                        <button type="submit" name="cancel_application" class="btn btn-action btn-cancel">
                                            <i class="fas fa-times"></i> Cancel Application
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    You have not applied for any jobs yet.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>