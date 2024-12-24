<?php
include('../db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: ../login.php");
    exit;
}

// Get payment statistics
$stats_sql = "SELECT 
    COUNT(*) as total_transactions,
    SUM(amount) as total_spent,
    COUNT(CASE WHEN MONTH(payment_date) = MONTH(CURRENT_DATE()) THEN 1 END) as transactions_this_month,
    SUM(CASE WHEN MONTH(payment_date) = MONTH(CURRENT_DATE()) THEN amount END) as spent_this_month,
    AVG(amount) as average_payment
    FROM payments 
    WHERE client_id = ? AND status = 'completed'";

$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get monthly payment trends
$trends_sql = "SELECT 
    DATE_FORMAT(payment_date, '%Y-%m') as month,
    COUNT(*) as transaction_count,
    SUM(amount) as total_amount
    FROM payments 
    WHERE client_id = ? AND status = 'completed'
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12";

$stmt = $conn->prepare($trends_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$trends = $stmt->get_result();

// Get payment methods distribution
$methods_sql = "SELECT 
    payment_method,
    COUNT(*) as usage_count,
    SUM(amount) as total_amount
    FROM payments 
    WHERE client_id = ? AND status = 'completed'
    GROUP BY payment_method";

$stmt = $conn->prepare($methods_sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$payment_methods = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stats-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
        }

        .stats-card:hover {
            border-color: var(--gold);
            transform: translateY(-2px);
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--gold);
        }

        .stat-label {
            color: var(--silver);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .chart-title {
            color: var(--gold);
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="../earnings.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i> Back to Earnings
            </a>
            <h2 class="text-gold mb-0">Payment Analytics</h2>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stat-label">Total Transactions</div>
                    <div class="stat-value"><?= number_format($stats['total_transactions']) ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stat-label">Total Amount Spent</div>
                    <div class="stat-value">₹<?= number_format($stats['total_spent'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stat-label">This Month</div>
                    <div class="stat-value">₹<?= number_format($stats['spent_this_month'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stat-label">Average Payment</div>
                    <div class="stat-value">₹<?= number_format($stats['average_payment'], 2) ?></div>
                </div>
            </div>
        </div>

        <!-- Monthly Trends Chart -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h3 class="chart-title">Monthly Payment Trends</h3>
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h3 class="chart-title">Payment Methods</h3>
                    <canvas id="methodsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Monthly Trends Chart
        const trendsData = {
            labels: [<?php 
                $labels = [];
                $amounts = [];
                while($trend = $trends->fetch_assoc()) {
                    $labels[] = "'" . date('M Y', strtotime($trend['month'] . '-01')) . "'";
                    $amounts[] = $trend['total_amount'];
                }
                echo implode(',', array_reverse($labels));
            ?>],
            datasets: [{
                label: 'Monthly Payments',
                data: [<?= implode(',', array_reverse($amounts)) ?>],
                borderColor: '#d4af37',
                backgroundColor: 'rgba(212, 175, 55, 0.1)',
                fill: true
            }]
        };

        new Chart('trendsChart', {
            type: 'line',
            data: trendsData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: 'white'
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: { color: 'white' }
                    },
                    x: {
                        ticks: { color: 'white' }
                    }
                }
            }
        });

        // Payment Methods Chart
        const methodsData = {
            labels: [<?php 
                $method_labels = [];
                $method_amounts = [];
                while($method = $payment_methods->fetch_assoc()) {
                    $method_labels[] = "'" . ucfirst($method['payment_method']) . "'";
                    $method_amounts[] = $method['usage_count'];
                }
                echo implode(',', $method_labels);
            ?>],
            datasets: [{
                data: [<?= implode(',', $method_amounts) ?>],
                backgroundColor: ['#d4af37', '#c0c0c0', '#cd7f32']
            }]
        };

        new Chart('methodsChart', {
            type: 'doughnut',
            data: methodsData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'white'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
