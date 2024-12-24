<?php
include('../db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit;
}

// Get transaction ID from URL
$transaction_id = isset($_GET['txn']) ? $_GET['txn'] : null;

if (!$transaction_id) {
    header("Location: payment_history.php");
    exit;
}

// Fetch payment status
$sql = "SELECT p.*, 
        j.title as job_title,
        u.email as freelancer_email,
        u.first_name as freelancer_name
        FROM payments p
        JOIN jobs j ON p.job_id = j.id
        JOIN users u ON p.freelancer_id = u.id
        WHERE p.transaction_id = ? AND p.client_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $transaction_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: payment_history.php");
    exit;
}

$payment = $result->fetch_assoc();

// Add this right after fetching the payment
if ($payment['status'] === 'completed') {
    echo "<script>
        setTimeout(function() {
            window.location.href = '../earnings.php?success=1';
        }, 3000);
    </script>";
}

// Get payment status timeline
$timeline_sql = "SELECT * FROM payment_status_logs 
                WHERE payment_id = ? 
                ORDER BY created_at ASC";
$stmt = $conn->prepare($timeline_sql);
$stmt->bind_param("i", $payment['id']);
$stmt->execute();
$timeline = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - <?= $transaction_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a0a0a;
            --secondary: #1a1a1a;
            --accent: #2a2a2a;
            --gold: #d4af37;
            --silver: #c0c0c0;
        }

        body {
            background-color: var(--primary);
            font-family: 'Inter', -apple-system, sans-serif;
            color: white;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 16px;
            padding: 2rem;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gold);
            opacity: 0.3;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2rem;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--gold);
            border: 2px solid var(--primary);
        }

        .timeline-item.completed::before {
            background: #00c853;
        }

        .timeline-item.pending::before {
            background: #ffd600;
        }

        .timeline-item.failed::before {
            background: #f44336;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-completed {
            background: rgba(0, 200, 83, 0.1);
            color: #00c853;
        }

        .status-pending {
            background: rgba(255, 214, 0, 0.1);
            color: #ffd600;
        }

        .status-failed {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        .refresh-button {
            color: var(--gold);
            border: 1px solid var(--gold);
            background: transparent;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .refresh-button:hover {
            background: var(--gold);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="payment_history.php" class="text-gold text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i> Back to Payment History
            </a>
            <button class="refresh-button" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i> Refresh Status
            </button>
        </div>

        <div class="status-card mb-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h4 class="text-gold mb-1">Payment Status</h4>
                    <p class="mb-0"><?= htmlspecialchars($payment['job_title']) ?></p>
                </div>
                <span class="status-badge status-<?= strtolower($payment['status']) ?>">
                    <?= ucfirst($payment['status']) ?>
                </span>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <p class="text-silver mb-1">Transaction ID</p>
                    <p class="mb-3"><?= htmlspecialchars($payment['transaction_id']) ?></p>
                    
                    <p class="text-silver mb-1">Amount</p>
                    <p class="mb-3">₹<?= number_format($payment['amount'], 2) ?></p>
                </div>
                <div class="col-md-6">
                    <p class="text-silver mb-1">Payment Date</p>
                    <p class="mb-3"><?= date('F j, Y g:i A', strtotime($payment['payment_date'])) ?></p>
                    
                    <p class="text-silver mb-1">Recipient</p>
                    <p class="mb-3"><?= htmlspecialchars($payment['freelancer_name']) ?></p>
                </div>
            </div>

            <div class="timeline">
                <?php while ($event = $timeline->fetch_assoc()): ?>
                    <div class="timeline-item <?= strtolower($event['status']) ?>">
                        <p class="mb-1 text-gold">
                            <?= htmlspecialchars($event['status_message']) ?>
                        </p>
                        <small class="text-silver">
                            <?= date('M d, Y H:i', strtotime($event['created_at'])) ?>
                        </small>
                    </div>
                <?php endwhile; ?>

                <?php if ($payment['status'] === 'completed'): ?>
                    <div class="timeline-item completed">
                        <p class="mb-1 text-gold">Payment Successfully Processed</p>
                        <small class="text-silver">
                            <?= date('M d, Y H:i', strtotime($payment['payment_date'])) ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($payment['status'] === 'completed' && !empty($payment['receipt_url'])): ?>
            <div class="text-center">
                <a href="<?= htmlspecialchars($payment['receipt_url']) ?>" 
                   class="btn btn-outline-light" 
                   target="_blank">
                    <i class="fas fa-download me-2"></i>Download Receipt
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh status every 30 seconds for pending payments
        <?php if ($payment['status'] === 'pending'): ?>
        setInterval(() => {
            location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
