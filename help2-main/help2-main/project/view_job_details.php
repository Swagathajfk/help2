<?php
include('db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch job details with related info
$sql = "SELECT j.*, 
        f.email as freelancer_email,
        c.email as client_email,
        COUNT(ja.application_id) as applications_count
        FROM jobs j
        LEFT JOIN users f ON j.freelancer_id = f.id
        LEFT JOIN users c ON j.assigned_to = c.id
        LEFT JOIN job_applications ja ON j.id = ja.job_id
        WHERE j.id = ?
        GROUP BY j.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

// Fetch applications for this job
$apps_sql = "SELECT ja.*, u.email, u.first_name, u.surname
             FROM job_applications ja
             JOIN users u ON ja.client_id = u.id
             WHERE ja.job_id = ?";
$apps_stmt = $conn->prepare($apps_sql);
$apps_stmt->bind_param("i", $job_id);
$apps_stmt->execute();
$applications = $apps_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .badge { font-size: 0.9em; }
        .application-card { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Job Details</h2>
            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h3><?= htmlspecialchars($job['title']) ?></h3>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= $job['status'] == 'approved' ? 'success' : 
                                ($job['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($job['status']) ?>
                            </span>
                        </p>
                        <p><strong>Posted By:</strong> <?= htmlspecialchars($job['freelancer_email']) ?></p>
                        <p><strong>Budget:</strong> ₹<?= number_format($job['reward'], 2) ?></p>
                        <p><strong>Job Type:</strong> <?= htmlspecialchars($job['job_type']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Start Date:</strong> <?= date('M d, Y', strtotime($job['start_date'])) ?></p>
                        <p><strong>End Date:</strong> <?= date('M d, Y', strtotime($job['end_date'])) ?></p>
                        <p><strong>Total Applications:</strong> <?= $job['applications_count'] ?></p>
                        <?php if($job['assigned_to']): ?>
                            <p><strong>Assigned To:</strong> <?= htmlspecialchars($job['client_email']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3">
                    <h5>Description</h5>
                    <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                </div>
                <?php if(!empty($job['tags'])): ?>
                    <div class="mt-3">
                        <h5>Tags</h5>
                        <?php foreach(json_decode($job['tags'], true) as $tag): ?>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <h4 class="mt-4 mb-3">Applications</h4>
        <?php if($applications->num_rows > 0): ?>
            <?php while($app = $applications->fetch_assoc()): ?>
                <div class="card application-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5><?= htmlspecialchars($app['email']) ?></h5>
                                <p class="text-muted mb-2">
                                    Applied on: <?= date('M d, Y', strtotime($app['applied_date'])) ?>
                                </p>
                            </div>
                            <span class="badge bg-<?= $app['status'] == 'accepted' ? 'success' : 
                                ($app['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($app['status']) ?>
                            </span>
                        </div>
                        <p><strong>Why Hire:</strong> <?= nl2br(htmlspecialchars($app['why_hire'])) ?></p>
                        <p><strong>Contact Info:</strong> <?= htmlspecialchars($app['contact_info']) ?></p>
                        <p><strong>Meeting Preference:</strong> <?= ucfirst($app['meeting_preference']) ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">No applications yet.</div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>