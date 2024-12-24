<?php
include('db_connect.php');
session_start();

// Add debug logging
error_log("Session user_id: " . $_SESSION['user_id'] ?? 'not set');
error_log("Session role: " . $_SESSION['role'] ?? 'not set');

// Check both session and role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    error_log("Unauthorized access attempt to job_details.php");
    header("Location: login.php");
    exit;
}

if (!isset($_GET['job_id'])) {
    header("Location: jobs.php");
    exit;
}

$job_id = $_GET['job_id'];

// Update the SQL query to join with users table and check role
$sql = "SELECT j.*, j.id as job_id, j.freelancer_id,
               j.deadline, j.start_date, j.end_date, 
               j.payment_status, j.payment_date, 
               u.email AS freelancer_email,
               u.role AS user_role 
        FROM jobs j 
        JOIN users u ON j.freelancer_id = u.id 
        WHERE j.id = ? AND u.role = 'freelancer'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Job not found!";
    exit;
}

$job = $result->fetch_assoc();

// Fetch associated job files
$job_files_sql = "SELECT file_path FROM job_files WHERE job_id = ?";
$stmt_files = $conn->prepare($job_files_sql);
$stmt_files->bind_param("i", $job_id);
$stmt_files->execute();
$files_result = $stmt_files->get_result();

$job_files = [];
while ($file_row = $files_result->fetch_assoc()) {
    $job_files[] = $file_row['file_path'];
}

// Fetch job edit history
$job_edits_sql = "SELECT updated_description, updated_at FROM job_edits WHERE job_id = ? ORDER BY updated_at DESC";
$stmt_edits = $conn->prepare($job_edits_sql);
$stmt_edits->bind_param("i", $job_id);
$stmt_edits->execute();
$edits_result = $stmt_edits->get_result();

$job_edits = [];
while ($edit_row = $edits_result->fetch_assoc()) {
    $job_edits[] = $edit_row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - <?= htmlspecialchars($job['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@coreui/icons@2.0.0-beta.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1c1c1e;
            --sidebar-hover: #2c2c2e;
            --card-bg: #ffffff;
            --text-primary: #000000;
            --text-secondary: #6e6e73;
            --accent-blue: #0071e3;
            --accent-green: #00b06b;
            --accent-yellow: #ffd60a;
            --shadow-sm: rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', sans-serif;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            width: 280px;
            min-height: 100vh;
            position: fixed;
            padding: 1.5rem 1rem;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.9rem 1.25rem;
            margin: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            transform: translateX(4px);
            color: white;
            text-decoration: none;
        }

        .nav-link.active {
            background: var(--accent-blue);
            color: white;
            font-weight: 600;
        }

        .content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        .job-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .job-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .job-title {
            color: var(--text-primary);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .meta-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .meta-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .meta-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .file-list {
            list-style: none;
            padding: 0;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .edit-history {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .edit-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .edit-timestamp {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .back-button {
            background: var(--accent-blue);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 113, 227, 0.2);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .content {
                margin-left: 0;
            }
            .job-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
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
                    <a class="nav-link active" href="jobs.php">
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
                    <a class="nav-link" href="notifications.php">
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

        <!-- Main content -->
        <div class="content">
            <div class="job-card">
                <div class="job-header">
                    <h1 class="job-title"><?= htmlspecialchars($job['title']) ?></h1>
                    <p class="text-secondary mb-0">Posted by <?= htmlspecialchars($job['freelancer_email']) ?></p>
                </div>

                <div class="job-meta">
                    <div class="meta-item">
                        <div class="meta-label">Job Type</div>
                        <div class="meta-value"><?= htmlspecialchars($job['job_type']) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Reward</div>
                        <div class="meta-value">₹<?= number_format($job['reward'], 2) ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Deadline</div>
                        <div class="meta-value"><?= htmlspecialchars($job['deadline']) ?></div>
                    </div>
                    <?php if ($job['start_date']): ?>
                    <div class="meta-item">
                        <div class="meta-label">Start Date</div>
                        <div class="meta-value"><?= htmlspecialchars($job['start_date']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($job['end_date']): ?>
                    <div class="meta-item">
                        <div class="meta-label">End Date</div>
                        <div class="meta-value"><?= htmlspecialchars($job['end_date']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="description-section">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                </div>

                <!-- New Payment Card Section -->
                <div class="card payment-summary-card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-2">Payment to Client</h4>
                                <h2 class="mb-3">₹<?= number_format($job['reward'], 2) ?></h2>
                                <p class="text-muted mb-0">
                                    <?php if ($job['payment_status'] === 'paid'): ?>
                                        <i class="fas fa-check-circle text-success"></i> 
                                        Paid on <?= date('M d, Y', strtotime($job['payment_date'])) ?>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle text-info"></i> 
                                        Payment Due to Client
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php 
                            // Check if current user is the freelancer who posted this job
                            if ($_SESSION['role'] === 'freelancer' && 
                                $job['freelancer_id'] == $_SESSION['user_id'] && 
                                $job['payment_status'] === 'unpaid'): 
                            ?>
                                <button type="button" class="btn btn-lg btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Pay to Client
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($job_files)): ?>
                <div class="files-section">
                    <h3>Attachments</h3>
                    <ul class="file-list">
                        <?php foreach ($job_files as $file_path): ?>
                        <li class="file-item">
                            <i class="cil-file"></i>
                            <a href="<?= htmlspecialchars($file_path) ?>" target="_blank">View File</a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($job_edits)): ?>
                <div class="edit-history">
                    <h3>Edit History</h3>
                    <?php foreach ($job_edits as $edit): ?>
                    <div class="edit-item">
                        <div class="edit-timestamp">
                            <i class="cil-history"></i>
                            Edited on <?= htmlspecialchars($edit['updated_at']) ?>
                        </div>
                        <div class="edit-content">
                            <?= nl2br(htmlspecialchars($edit['updated_description'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="jobs.php" class="back-button">
                        <i class="cil-arrow-left"></i>
                        Back to Job Listings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="paymentForm">
                    <div class="modal-body">
                        <input type="hidden" name="job_id" value="<?= $job_id ?>">
                        <input type="hidden" name="amount" value="<?= $job['reward'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" name="card_number" 
                                   required pattern="\d{16}" maxlength="16"
                                   placeholder="1234 5678 9012 3456">
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" name="expiry_date" 
                                       required pattern="\d{2}/\d{2}" maxlength="5"
                                       placeholder="MM/YY">
                            </div>
                            <div class="col">
                                <label class="form-label">CVV</label>
                                <input type="password" class="form-control" name="cvv" 
                                       required pattern="\d{3}" maxlength="3"
                                       placeholder="123">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" name="cardholder_name" 
                                   required placeholder="John Doe">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Billing Address</label>
                            <textarea class="form-control" name="billing_address" 
                                      required rows="2"></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Total Amount: ₹<?= number_format($job['reward'], 2) ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-lock me-2"></i>Pay Securely
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap Modal
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all modals
            var paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            
            // Update the openPaymentModal function
            window.openPaymentModal = function(jobId) {
                paymentModal.show();
            }
        });

        // Update the JavaScript payment handler
        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const submitButton = form.querySelector('[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            try {
                const response = await fetch('process_payment.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Payment successful!');
                    // Simply close the modal and refresh the current page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                    modal.hide();
                    location.reload(); // This will refresh the job details page showing updated payment status
                } else {
                    alert(data.message || 'Payment failed!');
                }
            } catch (err) {
                alert('Error processing payment');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-lock me-2"></i>Pay Securely';
            }
        });

        // Format expiry date input
        document.querySelector('[name="expiry_date"]').addEventListener('input', function(e) {
            this.value = this.value
                .replace(/\D/g, '')
                .replace(/^(\d{2})/, '$1/')
                .substr(0, 5);
        });
    </script>
</body>
</html>