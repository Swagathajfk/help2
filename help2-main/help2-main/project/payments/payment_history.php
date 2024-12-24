<?php
include('../db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit;
}

// Fetch all payments for this client
$sql = "SELECT p.*, j.title as job_title, u.email as freelancer_email 
        FROM payments p
        JOIN jobs j ON p.job_id = j.id
        JOIN users u ON p.freelancer_id = u.id
        WHERE p.client_id = ?
        ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$payments = $stmt->get_result();

// Calculate total payments
$total_sql = "SELECT SUM(amount) as total FROM payments WHERE client_id = ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("i", $_SESSION['user_id']);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_paid = $total_result->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
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
        }

        .payment-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            border-color: var(--gold);
        }

        .payment-header {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .payment-body {
            padding: 1rem;
        }

        .payment-details {
            display: grid;
            gap: 0.5rem;
            color: white;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-completed {
            background: rgba(0, 200, 83, 0.1);
            color: #00c853;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .status-failed {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        .summary-card {
            background: linear-gradient(45deg, var(--gold), var(--silver));
            border-radius: 12px;
            padding: 2rem;
            color: var(--primary);
            margin-bottom: 2rem;
        }

        .text-gold {
            color: var(--gold) !important;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <a href="../earnings.php" class="btn btn-outline-light mb-4">
            <i class="fas fa-arrow-left me-2"></i> Back to Earnings
        </a>
        
        <!-- Summary Section -->
        <div class="summary-card">
            <h2 class="mb-4">Payment Summary</h2>
            <div class="row">
                <div class="col-md-4">
                    <h3 class="h5">Total Amount Paid</h3>
                    <h4 class="display-6">₹<?= number_format($total_paid, 2) ?></h4>
                </div>
                <div class="col-md-4">
                    <h3 class="h5">Total Transactions</h3>
                    <h4 class="display-6"><?= $payments->num_rows ?></h4>
                </div>
            </div>
        </div>

        <h2 class="text-light mb-4">Payment History</h2>

        <?php if ($payments->num_rows > 0): ?>
            <?php while ($payment = $payments->fetch_assoc()): ?>
                <div class="payment-card">
                    <div class="payment-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="text-gold mb-0">
                                <?= htmlspecialchars($payment['job_title']) ?>
                            </h5>
                            <span class="status-badge status-<?= strtolower($payment['status']) ?>">
                                <?= ucfirst($payment['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="payment-body">
                        <div class="payment-details">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <i class="fas fa-money-bill-wave me-2"></i>
                                        Amount: ₹<?= number_format($payment['amount'], 2) ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-calendar me-2"></i>
                                        Date: <?= date('M d, Y H:i', strtotime($payment['payment_date'])) ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <i class="fas fa-user me-2"></i>
                                        To: <?= htmlspecialchars($payment['freelancer_email']) ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-hashtag me-2"></i>
                                        Transaction ID: <?= htmlspecialchars($payment['transaction_id']) ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!empty($payment['receipt_url'])): ?>
                                <div class="mt-2">
                                    <a href="<?= htmlspecialchars($payment['receipt_url']) ?>" 
                                       class="btn btn-sm btn-outline-light" 
                                       target="_blank">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        View Receipt
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                No payment history found.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
