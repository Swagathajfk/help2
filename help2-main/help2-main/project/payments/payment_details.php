<?php
include('../db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: payment_history.php");
    exit;
}

$payment_id = intval($_GET['id']);

// Fetch detailed payment information
$sql = "SELECT p.*, 
        j.title as job_title, j.description as job_description, j.job_type,
        j.start_date, j.end_date,
        u.email as freelancer_email, u.first_name as freelancer_first_name,
        u.surname as freelancer_surname
        FROM payments p
        JOIN jobs j ON p.job_id = j.id
        JOIN users u ON p.freelancer_id = u.id
        WHERE p.id = ? AND p.client_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $payment_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: payment_history.php");
    exit;
}

$payment = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details - #<?= $payment_id ?></title>
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

        .payment-details-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            color: var(--gold);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .info-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }

        .info-label {
            color: var(--silver);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: white;
            font-size: 1rem;
            font-weight: 500;
        }

        .receipt-section {
            background: linear-gradient(45deg, var(--gold), var(--silver));
            border-radius: 12px;
            padding: 2rem;
            color: var(--primary);
            margin-top: 2rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-completed {
            background: rgba(0, 200, 83, 0.1);
            color: #00c853;
        }

        .back-button {
            color: var(--gold);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .back-button:hover {
            transform: translateX(-5px);
            color: var(--silver);
        }

        @media print {
            body {
                background: white;
                color: black;
            }
            .payment-details-card {
                border: 1px solid #ddd;
                background: white;
            }
            .back-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <a href="payment_history.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Payment History
        </a>

        <div class="payment-details-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 text-gold mb-0">Payment Details</h1>
                <span class="status-badge status-<?= strtolower($payment['status']) ?>">
                    <?= ucfirst($payment['status']) ?>
                </span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Transaction ID</div>
                    <div class="info-value"><?= htmlspecialchars($payment['transaction_id']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Amount Paid</div>
                    <div class="info-value">₹<?= number_format($payment['amount'], 2) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Date</div>
                    <div class="info-value"><?= date('F j, Y g:i A', strtotime($payment['payment_date'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">
                        <i class="fas fa-credit-card me-2"></i>
                        Card ending in <?= htmlspecialchars($payment['card_last_four']) ?>
                    </div>
                </div>
            </div>

            <div class="section-title">Job Details</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Job Title</div>
                    <div class="info-value"><?= htmlspecialchars($payment['job_title']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Job Type</div>
                    <div class="info-value"><?= htmlspecialchars($payment['job_type']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Freelancer</div>
                    <div class="info-value">
                        <?= htmlspecialchars($payment['freelancer_first_name'] . ' ' . $payment['freelancer_surname']) ?>
                        <div class="text-muted small"><?= htmlspecialchars($payment['freelancer_email']) ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($payment['billing_address'])): ?>
                <div class="section-title">Billing Information</div>
                <div class="info-item">
                    <div class="info-label">Billing Address</div>
                    <div class="info-value"><?= nl2br(htmlspecialchars($payment['billing_address'])) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($payment['receipt_url'])): ?>
                <div class="receipt-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="h5 mb-3">Payment Receipt</h3>
                            <p class="mb-0">A copy of your receipt has been generated.</p>
                        </div>
                        <a href="<?= htmlspecialchars($payment['receipt_url']) ?>" 
                           class="btn btn-dark" 
                           target="_blank">
                            <i class="fas fa-download me-2"></i>
                            Download Receipt
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <button onclick="window.print()" class="btn btn-outline-light">
                <i class="fas fa-print me-2"></i> Print Details
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
