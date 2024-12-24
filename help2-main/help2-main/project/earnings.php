<?php
include('db_connect.php');
session_start();

// Only clients can view earnings since they receive payments
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Get client's earnings
$stats_sql = "SELECT 
    COUNT(*) as total_received,
    COALESCE(SUM(amount), 0) as total_earnings,
    COUNT(CASE WHEN DATE(payment_date) = CURDATE() THEN 1 END) as payments_today,
    COALESCE(SUM(CASE WHEN DATE(payment_date) = CURDATE() THEN amount END), 0) as earned_today,
    COUNT(CASE WHEN MONTH(payment_date) = MONTH(CURRENT_DATE()) THEN 1 END) as payments_this_month,
    COALESCE(SUM(CASE WHEN MONTH(payment_date) = MONTH(CURRENT_DATE()) THEN amount END), 0) as earned_this_month
    FROM payments 
    WHERE client_id = ? AND status = 'completed'";

$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent payments received from freelancers
$recent_sql = "SELECT p.*, j.title as job_title, u.first_name as freelancer_name 
              FROM payments p
              JOIN jobs j ON p.job_id = j.id
              JOIN users u ON p.freelancer_id = u.id
              WHERE p.client_id = ?
              ORDER BY p.payment_date DESC LIMIT 5";

$stmt = $conn->prepare($recent_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recent_payments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings & Payments - Client Dashboard</title>
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
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            font-family: 'Inter', -apple-system, sans-serif;
            color: white;
            min-height: 100vh;
        }

        /* Enhanced Cards */
        .stats-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            height: 100%;
            backdrop-filter: blur(10px);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.1);
        }

        .stats-card h6 {
            color: var(--gold);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        .stats-card h3 {
            font-size: 2rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
        }

        .stats-card small {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }

        /* Table Styling */
        .table-container {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 16px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .table-header {
            background: rgba(212, 175, 55, 0.1);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .table-dark {
            background: transparent;
            margin: 0;
        }

        .table-dark td, .table-dark th {
            border-color: rgba(255, 255, 255, 0.1);
            padding: 1rem 1.5rem;
            vertical-align: middle;
        }

        .table-dark thead th {
            background: rgba(0, 0, 0, 0.2);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            color: var(--gold);
        }

        /* Action Buttons */
        .btn-outline-gold {
            color: var(--gold);
            border-color: var(--gold);
            background: transparent;
            transition: all 0.3s ease;
        }

        .btn-outline-gold:hover {
            background: var(--gold);
            color: var(--primary);
        }

        /* Status Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .badge.bg-success {
            background: rgba(25, 135, 84, 0.1) !important;
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .badge.bg-warning {
            background: rgba(255, 193, 7, 0.1) !important;
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        /* Page Title */
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--gold), var(--silver));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0;
        }

        /* Action Buttons Container */
        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .action-buttons .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Animated Background */
        .background-animation {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 100vw;
            height: 100vh;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle at center, 
                rgba(212, 175, 55, 0.1) 0%, 
                rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
            z-index: -1;
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
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
                        <a class="nav-link" href="earnings.php">
                            <i class="fas fa-wallet"></i> Earnings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client_notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                </ul>
                <form class="d-flex me-3" action="search.php" method="GET">
                    <input class="form-control me-2 search-input" type="search" placeholder="Search" name="query">
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
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                Payment received successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="page-title">Financial Overview</h1>
                    <div class="action-buttons">
                        <a href="payments/payment_history.php" class="btn btn-outline-light">
                            <i class="fas fa-history"></i>
                            Payment History
                        </a>
                        <a href="payments/payment_reports.php" class="btn btn-outline-gold">
                            <i class="fas fa-chart-bar"></i>
                            Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>Total Earnings</h6>
                        <h3>₹<?= number_format($stats['total_earnings'] ?? 0, 2) ?></h3>
                        <small><?= $stats['total_received'] ?? 0 ?> payments received</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>This Month</h6>
                        <h3>₹<?= number_format($stats['earned_this_month'] ?? 0, 2) ?></h3>
                        <small><?= $stats['payments_this_month'] ?? 0 ?> payments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>Today</h6>
                        <h3>₹<?= number_format($stats['earned_today'] ?? 0, 2) ?></h3>
                        <small><?= $stats['payments_today'] ?? 0 ?> payments today</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="table-container">
            <div class="table-header">
                <h5 class="mb-0 text-gold">Recent Payments Received</h5>
            </div>
            <div class="card-body">
                <?php if ($recent_payments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-dark">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Job</th>
                                    <th>From</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = $recent_payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                        <td><?= htmlspecialchars($payment['job_title']) ?></td>
                                        <td><?= htmlspecialchars($payment['freelancer_name']) ?></td>
                                        <td>₹<?= number_format($payment['amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $payment['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($payment['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="payments/payment_details.php?id=<?= $payment['id'] ?>" 
                                               class="btn btn-sm btn-outline-light">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center mb-0">No payments received yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
