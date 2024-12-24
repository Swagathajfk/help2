<?php
include('db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch assignment details
$sql = "SELECT j.*, 
        f.email as freelancer_email, f.first_name as freelancer_fname, f.surname as freelancer_sname,
        c.email as client_email, c.first_name as client_fname, c.surname as client_sname,
        ja.applied_date, ja.why_hire, ja.contact_info, ja.meeting_preference
        FROM jobs j
        JOIN users f ON j.freelancer_id = f.id
        JOIN users c ON j.assigned_to = c.id
        JOIN job_applications ja ON j.id = ja.job_id AND ja.client_id = j.assigned_to
        WHERE j.id = ? AND j.status = 'assigned'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Details - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card { box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .user-info { background-color: #f8f9fa; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Assignment Details</h2>
            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Freelancer Information</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= htmlspecialchars($assignment['freelancer_fname'] . ' ' . $assignment['freelancer_sname']) ?></h6>
                        <p class="mb-2"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($assignment['freelancer_email']) ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Client Information</h5>
                    </div>
                    <div class="card-body">
                        <h6><?= htmlspecialchars($assignment['client_fname'] . ' ' . $assignment['client_sname']) ?></h6>
                        <p class="mb-2"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($assignment['client_email']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Job Details</h5>
            </div>
            <div class="card-body">
                <h4><?= htmlspecialchars($assignment['title']) ?></h4>
                <p class="text-muted">Posted on: <?= date('M d, Y', strtotime($assignment['created_at'])) ?></p>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong>Budget:</strong> ₹<?= number_format($assignment['reward'], 2) ?></p>
                        <p><strong>Job Type:</strong> <?= htmlspecialchars($assignment['job_type']) ?></p>
                        <p><strong>Start Date:</strong> <?= date('M d, Y', strtotime($assignment['start_date'])) ?></p>
                        <p><strong>End Date:</strong> <?= date('M d, Y', strtotime($assignment['end_date'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Assignment Date:</strong> <?= date('M d, Y', strtotime($assignment['applied_date'])) ?></p>
                        <p><strong>Meeting Preference:</strong> <?= ucfirst($assignment['meeting_preference']) ?></p>
                        <p><strong>Contact Info:</strong> <?= htmlspecialchars($assignment['contact_info']) ?></p>
                    </div>
                </div>

                <div class="mt-3">
                    <h5>Job Description</h5>
                    <p><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                </div>

                <div class="mt-3">
                    <h5>Client's Application Note</h5>
                    <p><?= nl2br(htmlspecialchars($assignment['why_hire'])) ?></p>
                </div>

                <?php if(!empty($assignment['tags'])): ?>
                    <div class="mt-3">
                        <h5>Tags</h5>
                        <?php foreach(json_decode($assignment['tags'], true) as $tag): ?>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>