<?php
include('db_connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (!empty($search_query)) {
    // Search in jobs table
    $sql = "SELECT j.*, 
            u.email AS freelancer_email,
            COALESCE(ua.email, 'Not Assigned') as assigned_to_user,
            (SELECT COUNT(*) 
             FROM job_applications 
             WHERE job_id = j.id AND client_id = ?) as has_applied,
            (SELECT status 
             FROM job_applications 
             WHERE job_id = j.id AND client_id = ?) as application_status
            FROM jobs j 
            JOIN users u ON j.freelancer_id = u.id 
            LEFT JOIN users ua ON j.assigned_to = ua.id 
            WHERE (j.title LIKE ? OR j.description LIKE ? OR j.tags LIKE ?)
            AND (j.status = 'open' OR j.assigned_to = ?)
            ORDER BY j.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    $search_param = "%$search_query%";
    $stmt->bind_param("iisssi", 
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $search_param,
        $search_param,
        $search_param,
        $_SESSION['user_id']
    );
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Freelancing Site</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a1a2e;
            --secondary-color: #16213e;
            --accent-color: #0f3460;
            --light-accent: #e94560;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: var(--primary-color);
        }

        .navbar {
            background: linear-gradient(90deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .search-input {
            background-color: rgba(255,255,255,0.1) !important;
            color: white !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
        }

        .search-input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .job-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 20px;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .job-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .tag-badge {
            background-color: var(--light-accent);
            color: white;
            margin-right: 5px;
            margin-bottom: 5px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: var(--light-accent);
            border-color: var(--light-accent);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="https://via.placeholder.com/40" alt="Logo" class="rounded-circle">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <?php if ($_SESSION['role'] == 'client'): ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="freelancer_dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_job_posts.php">
                                <i class="fas fa-briefcase"></i> My Jobs
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <form class="d-flex me-3" action="search.php" method="GET">
                    <input class="form-control me-2 search-input" type="search" 
                           placeholder="Search" name="query" value="<?= htmlspecialchars($search_query) ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <a href="logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4">
            <?php if (!empty($search_query)): ?>
                Search Results for "<?= htmlspecialchars($search_query) ?>"
            <?php else: ?>
                Please enter a search term
            <?php endif; ?>
        </h2>

        <?php if (!empty($search_query)): ?>
            <div class="row">
                <?php if (isset($result) && $result->num_rows > 0): ?>
                    <?php while ($job = $result->fetch_assoc()): ?>
                        <div class="col-md-4 mb-4">
                            <div class="job-card">
                                <?php if (!empty($job['job_image'])): ?>
                                    <img src="<?= htmlspecialchars($job['job_image']) ?>" 
                                         class="job-image" alt="Job Image">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                                    <p class="card-text">
                                        <?= substr(htmlspecialchars($job['description']), 0, 150) ?>...
                                    </p>
                                    <div class="mb-3">
                                        <strong>Job Type:</strong> <?= htmlspecialchars($job['job_type']) ?><br>
                                        <strong>Budget:</strong> ₹<?= number_format($job['reward'], 2) ?><br>
                                        <strong>Posted By:</strong> <?= htmlspecialchars($job['freelancer_email']) ?>
                                    </div>
                                    
                                    <?php if (!empty($job['tags'])): ?>
                                        <div class="mb-3">
                                            <?php foreach (json_decode($job['tags'], true) as $tag): ?>
                                                <span class="tag-badge">
                                                    <?= htmlspecialchars(str_replace('-', ' ', $tag)) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($_SESSION['role'] == 'client'): ?>
                                        <?php if ($job['has_applied'] == 0): ?>
                                            <button type="button" class="btn btn-primary w-100" 
                                                    onclick="openApplyModal(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title']) ?>')">
                                                Apply Now
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary w-100" disabled>
                                                Already Applied
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No jobs found matching your search criteria.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Apply Modal (Only for clients) -->
    <?php if ($_SESSION['role'] == 'client'): ?>
    <div class="modal fade" id="applyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Apply for: <span id="jobTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="applicationForm">
                    <div class="modal-body">
                        <input type="hidden" name="job_id" id="modalJobId">
                        <div class="mb-3">
                            <label class="form-label">Why should you be hired?</label>
                            <textarea name="why_hire" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Information</label>
                            <input type="text" name="contact_info" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meeting Preference</label>
                            <select name="meeting_preference" class="form-control" required>
                                <option value="">Select preference</option>
                                <option value="online">Online Meeting</option>
                                <option value="in-person">In-Person Meeting</option>
                                <option value="phone">Phone Call</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($_SESSION['role'] == 'client'): ?>
    <script>
        function openApplyModal(jobId, jobTitle) {
            document.getElementById('modalJobId').value = jobId;
            document.getElementById('jobTitle').textContent = jobTitle;
            new bootstrap.Modal(document.getElementById('applyModal')).show();
        }

        document.getElementById('applicationForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('apply_job.php', {
                    method: 'POST',
                    body: new FormData(e.target)
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Error submitting application');
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>