<?php
session_start();
$error_message = "";

switch ($_GET['error'] ?? '') {
    case 'invalid_request':
        $error_message = "Invalid payment request. Please try again.";
        break;
    case 'invalid_job':
        $error_message = "This job is not available for payment or has already been paid.";
        break;
    case 'amount_mismatch':
        $error_message = "Payment amount does not match the job reward.";
        break;
    case 'database':
        $error_message = "A system error occurred while processing your payment.";
        break;
    case 'payment':
        $error_message = "Payment processing failed. Please try again.";
        break;
    case 'payment_not_found':
        $error_message = "Payment record not found.";
        break;
    default:
        $error_message = "An unexpected error occurred.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .error-icon { font-size: 5rem; color: #dc3545; }
        .error-message { background: #f8d7da; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-body text-center">
                <div class="error-icon mb-3">✕</div>
                <h2 class="card-title text-danger">Payment Failed</h2>
                <div class="error-message mt-4">
                    <p class="mb-0"><?= htmlspecialchars($error_message) ?></p>
                </div>
                <div class="mt-4">
                    <a href="javascript:history.back()" class="btn btn-primary">Try Again</a>
                    <a href="jobs.php" class="btn btn-secondary">View All Jobs</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
