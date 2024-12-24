<?php
include('db_connect.php');
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: login.php");
    exit;
}
$sql = "SELECT j.*, j.files, u.first_name, u.surname, 
        u.email AS freelancer_email,
        COALESCE(ua.email, 'Not Assigned') as assigned_to_user,
        (SELECT COUNT(*) 
         FROM job_applications 
         WHERE job_id = j.id AND client_id = ?) as has_applied,
        (SELECT status 
         FROM job_applications 
         WHERE job_id = j.id AND client_id = ?) as application_status,
        (SELECT COUNT(*) 
         FROM job_applications 
         WHERE job_id = j.id AND status = 'accepted') as has_accepted_application
        FROM jobs j 
        JOIN users u ON j.freelancer_id = u.id 
        LEFT JOIN users ua ON j.assigned_to = ua.id 
        WHERE (j.status = 'open' OR j.assigned_to = ?)
        ORDER BY j.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a0a0a;
            --secondary: #1a1a1a;
            --accent: #2a2a2a;
            --gold: #d4af37;
            --silver: #c0c0c0;
            --text: #333;
        }

        body {
            background-color: var(--primary);
            font-family: 'Inter', -apple-system, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        .background-animation {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 800px;
            height: 800px;
            transform: translate(-50%, -50%);
            background: linear-gradient(45deg, var(--gold) 0%, var(--primary) 25%, var(--gold) 50%, var(--primary) 75%, var(--gold) 100%);
            opacity: 0.03;
            filter: blur(50px);
            animation: rotate 20s linear infinite;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes rotate {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Container for content */
        .container {
            position: relative;
            z-index: 1;
        }

        /* Updated Navbar */
        .navbar {
            background: var(--secondary) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
        }

        .navbar-brand {
            color: var(--gold) !important;
            font-size: 1.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Enhanced Job Cards - Updated for consistent sizing */
        .job-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(212, 175, 55, 0.1);
            backdrop-filter: blur(20px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            height: 100%;  /* Make all cards same height in their row */
            display: flex;
            flex-direction: column;
        }

        .job-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold);
        }

        /* Fixed image container size */
        .job-image-container {
            height: 200px;  /* Fixed height for all image containers */
            overflow: hidden;
            position: relative;
            background: var(--secondary);
        }

        .job-image {
            width: 100%;
            height: 100%;
            object-fit: cover;  /* This will maintain aspect ratio while filling container */
            object-position: center;  /* Center the image */
        }

        .card-body {
            flex: 1;  /* Allow card body to fill remaining space */
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        /* Push buttons/alerts to bottom of card */
        .card-actions {
            margin-top: auto;
        }

        /* Section Title */
        h1 {
            color: var(--gold) !important;
            font-weight: 800;
            position: relative;
            display: inline-block;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), transparent);
        }

        /* Filter Button */
        .btn-outline-primary {
            color: var(--gold);
            border-color: var(--gold);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--gold);
            color: var(--primary);
        }

        // ...existing root variables and general styles...

        /* Row styling for better card spacing */
        .row {
            margin: 0 -1rem;  /* Compensate for card padding */
        }

        .col-md-4 {
            padding: 1rem;  /* Even spacing between cards */
        }

        /* Enhanced Job Cards */
        .job-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.1);
            transition: all 0.3s ease;
            margin: 0;  /* Remove default margins */
        }

        .job-image-container {
            height: 180px;  /* Consistent image height */
            border-radius: 12px 12px 0 0;
            overflow: hidden;
        }

        .card-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;  /* Consistent spacing between elements */
        }

        .card-title {
            font-size: 1.25rem;
            margin: 0;
            color: var(--primary);
        }

        .job-details {
            display: grid;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .job-details p {
            margin: 0;
            display: flex;
            justify-content: space-between;
        }

        .tag-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .tag-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            background: linear-gradient(45deg, var(--gold), var(--silver));
            color: var(--primary);
            font-weight: 500;
        }

        .card-actions {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* Status button styling */
        .status-button {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Add animated background -->
    <div class="background-animation"></div>

    <!-- Update navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-diamond-fill me-2"></i>Kat
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
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
                    <li class="nav-item">
                        <a class="nav-link" href="client_notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
    <a class="nav-link" href="earnings.php">
        <i class="fas fa-wallet"></i> Earnings
    </a>
</li>
                </ul>
                <form class="d-flex me-3" action="search.php" method="GET">
                    <input class="form-control me-2 search-input" type="search" placeholder="Search" name="query" aria-label="Search">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <a href="logout.php" class="btn btn-outline-light rounded-pill">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    <div class="container py-5">
        <div class="row">
            <div class="col-12 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 style="color: var(--primary-color);">Available Jobs</h1>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="tagFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-tags"></i> Filter by Tags
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="tagFilterDropdown">
                            <li><button class="dropdown-item" data-tag="all">Show All</button></li>
                            <?php
                            $job_tags = [
                                'web-development', 'graphic-design', 'content-writing', 
                                'digital-marketing', 'data-analysis', 'mobile-app-development', 
                                'video-editing', 'translation', 'consulting'
                            ];
                            foreach($job_tags as $tag): ?>
                                <li>
                                    <button class="dropdown-item" data-tag="<?= $tag ?>">
                                        <?= ucwords(str_replace('-', ' ', $tag)) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($job = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card job-card" data-tags='<?= $job['tags'] ?>'>
                            <?php if (!empty($job['job_image'])): ?>
                                <div class="job-image-container">
                                    <img src="<?= htmlspecialchars($job['job_image']) ?>" class="job-image" alt="Job Image">
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                                <p class="card-text"><?= substr(htmlspecialchars($job['description']), 0, 150) ?>...</p>
                                
                                <div class="job-details">
                                    <p><strong>Job Type:</strong> <?= htmlspecialchars($job['job_type']) ?></p>
                                    <p><strong>Budget:</strong> ₹<?= number_format($job['reward'], 2) ?></p>
                                    <p><strong>Start Date:</strong> <?= date('M d, Y', strtotime($job['start_date'])) ?></p>
                                    <p><strong>End Date:</strong> <?= date('M d, Y', strtotime($job['end_date'])) ?></p>
                                    <p><strong>Posted By:</strong> <?= htmlspecialchars($job['freelancer_email']) ?></p>
                                </div>

                                <?php if (!empty($job['tags'])): ?>
                                    <div class="tag-container">
                                        <?php foreach (json_decode($job['tags'], true) as $tag): ?>
                                            <span class="tag-badge">
                                                <?= htmlspecialchars(str_replace('-', ' ', $tag)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                $files = json_decode($job['files'], true);
                                if (!empty($files)): 
                                ?>
                                    <div class="mb-4">
                                        <h6 class="font-semibold mb-2">Attached Files:</h6>
                                        <?php foreach ($files as $file): ?>
                                            <a href="<?= htmlspecialchars($file) ?>" 
                                            target="_blank" 
                                            class="text-blue-600 hover:text-blue-800 block mb-1">
                                            <i class="fas fa-file me-2"></i>View File
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="card-actions">
                                    <?php if ($job['status'] == 'open'): ?>
                                        <?php if ($job['has_applied'] == 0): ?>
                                            <button type="button" class="btn btn-primary w-100" onclick="openApplyModal(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title']) ?>')">
                                                Apply Now
                                            </button>
                                        <?php else: ?>
                                            <?php
                                            $status_classes = [
                                                'pending' => 'btn-warning',
                                                'reviewing' => 'btn-info',
                                                'accepted' => 'btn-success',
                                                'rejected' => 'btn-danger'
                                            ];
                                            $status_text = [
                                                'pending' => 'Application Pending',
                                                'reviewing' => 'Under Review',
                                                'accepted' => 'Application Accepted',
                                                'rejected' => 'Application Rejected'
                                            ];
                                            $status = $job['application_status'] ?? 'pending';
                                            ?>
                                            <button class="btn <?= $status_classes[$status] ?> w-100" disabled>
                                                <?= $status_text[$status] ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-secondary mb-0">
                                            Job taken by: <?= htmlspecialchars($job['assigned_to_user']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No jobs available at the moment. Check back later!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
    <script>
        // Add this after your existing scripts
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                const selectedTag = this.getAttribute('data-tag');
                const jobCards = document.querySelectorAll('.job-card');
                
                jobCards.forEach(card => {
                    if (selectedTag === 'all') {
                        card.closest('.col-md-4').style.display = 'block';
                        return;
                    }
                    
                    const tags = JSON.parse(card.getAttribute('data-tags') || '[]');
                    if (tags.includes(selectedTag)) {
                        card.closest('.col-md-4').style.display = 'block';
                    } else {
                        card.closest('.col-md-4').style.display = 'none';
                    }
                });
                // Update dropdown button text
                const buttonText = selectedTag === 'all' ? 
                    'Filter by Tags' : 
                    'Filtered: ' + selectedTag.split('-').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');
                document.getElementById('tagFilterDropdown').textContent = buttonText;
            });
        });
    </script>
</body>
</html>