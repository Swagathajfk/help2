<?php
include('db_connect.php');
session_start();

// Admin authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");  
    exit;
}

// Fetch users
$clients_query = "SELECT * FROM users WHERE role='client'";
$freelancers_query = "SELECT * FROM users WHERE role='freelancer'";
$clients = $conn->query($clients_query);
$freelancers = $conn->query($freelancers_query);

// Fetch all jobs with related info
$jobs_query = "SELECT j.*, 
               f.email as freelancer_email,
               c.email as client_email,
               COUNT(ja.application_id) as applications_count
               FROM jobs j
               LEFT JOIN users f ON j.freelancer_id = f.id
               LEFT JOIN users c ON j.assigned_to = c.id
               LEFT JOIN job_applications ja ON j.id = ja.job_id
               GROUP BY j.id
               ORDER BY j.created_at DESC";
$jobs = $conn->query($jobs_query);

// Handle job approval/rejection
if (isset($_POST['action']) && isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    $update_sql = "UPDATE jobs SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('si', $status, $job_id);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a1a2e;
            --secondary-color: #16213e;
            --accent-color: #0f3460;
            --light-accent: #e94560;
        }
        
        .admin-nav {
            background: var(--primary-color);
            color: white;
        }
        
        .nav-tabs .nav-link.active {
            background-color: var(--accent-color);
            color: white;
            border: none;
        }
        
        .nav-tabs .nav-link {
            color: var(--primary-color);
        }
        
        .action-btn {
            width: 38px;
            height: 38px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
        }

        .tag-filter {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar admin-nav">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1 text-white">Admin Dashboard</span>
            <div class="d-flex">
                <span class="text-white me-3">Welcome, Admin</span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="users-tab" data-bs-toggle="tab" href="#users">Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="jobs-tab" data-bs-toggle="tab" href="#jobs">Jobs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="assignments-tab" data-bs-toggle="tab" href="#assignments">Assignments</a>
            </li>
        </ul>

        <div class="tab-content mt-4">
            <!-- Users Tab -->
            <div class="tab-pane fade show active" id="users">
                <div class="row">
                    <div class="col-md-6">
                        <h3>Clients</h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($client = $clients->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $client['id'] ?></td>
                                        <td><?= htmlspecialchars($client['email']) ?></td>
                                        <td><?= $client['status'] ?? 'active' ?></td>
                                        <td>
                                            <button class="btn btn-warning action-btn" onclick="editUser(<?= $client['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger action-btn" onclick="deleteUser(<?= $client['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h3>Freelancers</h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($freelancer = $freelancers->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $freelancer['id'] ?></td>
                                        <td><?= htmlspecialchars($freelancer['email']) ?></td>
                                        <td><?= $freelancer['status'] ?? 'active' ?></td>
                                        <td>
                                            <button class="btn btn-warning action-btn" onclick="editUser(<?= $freelancer['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger action-btn" onclick="deleteUser(<?= $freelancer['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jobs Tab -->
            <div class="tab-pane fade" id="jobs">
                <div class="tag-filter">
                    <select class="form-select" id="tagFilter" onchange="filterJobs()">
                        <option value="">Filter by Tag</option>
                        <?php
                        $job_tags = [
                            'web-development',
                            'graphic-design',
                            'content-writing',
                            'digital-marketing',
                            'data-analysis',
                            'mobile-app-development',
                            'video-editing',
                            'translation',
                            'consulting'
                        ];
                        foreach ($job_tags as $tag):
                        ?>
                            <option value="<?= $tag ?>"><?= ucwords(str_replace('-', ' ', $tag)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Posted By</th>
                                <th>Status</th>
                                <th>Applications</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($job = $jobs->fetch_assoc()): ?>
                            <tr class="job-row" data-tags='<?= $job['tags'] ?>'>
                                <td><?= $job['id'] ?></td>
                                <td><?= htmlspecialchars($job['title']) ?></td>
                                <td><?= htmlspecialchars($job['freelancer_email']) ?></td>
                                <td><?= $job['status'] ?></td>
                                <td><?= $job['applications_count'] ?></td>
                                <td>
                                    <button class="btn btn-success action-btn" onclick="approveJob(<?= $job['id'] ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger action-btn" onclick="rejectJob(<?= $job['id'] ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button class="btn btn-info action-btn" onclick="viewDetails(<?= $job['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Assignments Tab -->
            <div class="tab-pane fade" id="assignments">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Job Title</th>
                                <th>Client</th>
                                <th>Freelancer</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $assignments_query = "SELECT j.id, j.title, 
                                                c.email as client_email,
                                                f.email as freelancer_email,
                                                j.status
                                                FROM jobs j
                                                JOIN users c ON j.assigned_to = c.id
                                                JOIN users f ON j.freelancer_id = f.id
                                                WHERE j.status = 'assigned'";
                            $assignments = $conn->query($assignments_query);
                            while ($assignment = $assignments->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $assignment['id'] ?></td>
                                <td><?= htmlspecialchars($assignment['title']) ?></td>
                                <td><?= htmlspecialchars($assignment['client_email']) ?></td>
                                <td><?= htmlspecialchars($assignment['freelancer_email']) ?></td>
                                <td><?= $assignment['status'] ?></td>
                                <td>
                                    <button class="btn btn-info action-btn" onclick="viewAssignment(<?= $assignment['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(userId) {
            window.location.href = `edit_user.php?id=${userId}`;
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }

        function approveJob(jobId) {
            if (confirm('Are you sure you want to approve this job?')) {
                updateJobStatus(jobId, 'approve');
            }
        }

        function rejectJob(jobId) {
            if (confirm('Are you sure you want to reject this job?')) {
                updateJobStatus(jobId, 'reject');
            }
        }

        function updateJobStatus(jobId, action) {
            fetch('update_job_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    job_id: jobId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating job status');
                }
            });
        }

        function viewDetails(jobId) {
            window.location.href = `view_job_details.php?id=${jobId}`;
        }

        function viewAssignment(jobId) {
            window.location.href = `view_assignment.php?id=${jobId}`;
        }

        function filterJobs() {
            const selectedTag = document.getElementById('tagFilter').value;
            const jobRows = document.querySelectorAll('.job-row');

            jobRows.forEach(row => {
                const tags = JSON.parse(row.getAttribute('data-tags') || '[]');
                if (!selectedTag || tags.includes(selectedTag)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    <script>
        function handleFetchError(error) {
            console.error('Fetch Error:', error);
            alert('Error: ' + error.message);
        }

        function approveJob(jobId) {
            if (confirm('Are you sure you want to approve this job?')) {
                console.log('Attempting to approve job:', jobId);  // Debug log
                updateJobStatus(jobId, 'approve');
            }
        }
        function rejectJob(jobId) {
            if (confirm('Are you sure you want to reject this job?')) {
                console.log('Attempting to reject job:', jobId);  // Debug log
                updateJobStatus(jobId, 'reject');
            }
        }

        function updateJobStatus(jobId, action) {
            console.log('Updating job status:', {jobId, action});  // Debug log
            
            fetch('update_job_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    job_id: jobId,
                    action: action
                })
            })
            .then(response => {
                console.log('Response status:', response.status); // Debug log
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data); // Debug log
                if (data.success) {
                    alert(`Job ${action}d successfully!`);
                    location.reload();
                } else {
                    throw new Error(data.message || `Failed to ${action} job`);
                }
            })
            .catch(handleFetchError);
        }
    </script>
</body>
</html>