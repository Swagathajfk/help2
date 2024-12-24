<?php
session_start();
require_once 'db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'freelancer') {
    header("Location: login.php");
    exit;
}

// Fetch freelancer's jobs
$freelancer_id = $_SESSION['user_id'];
$my_jobs_sql = "SELECT j.*, ja.status as application_status, ja.client_id as applicant_id
                FROM jobs j 
                LEFT JOIN job_applications ja ON j.id = ja.job_id AND ja.status = 'accepted'
                WHERE j.freelancer_id = ? 
                ORDER BY j.created_at DESC";
$stmt = $conn->prepare($my_jobs_sql);
$stmt->bind_param('i', $freelancer_id);
$stmt->execute();
$my_jobs_result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Job Posts</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/coreui@4.1.0/dist/css/coreui.min.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --sidebar-width: 280px;
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
            width: 100%;
        }

        /* Sidebar Styles */
        .sidebar {
            background: var(--sidebar-bg);
            min-width: var(--sidebar-width);
            width: var(--sidebar-width);
            min-height: 100vh;
            position: fixed;
            padding: 1.5rem 1rem;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 100;
        }

        .nav-link {
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            padding: 0.9rem 1.25rem;
            margin: 0.25rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: var(--accent-blue);
            color: white;
            font-weight: 600;
        }

        .nav-link i {
            font-size: 1.1rem;
            opacity: 0.75;
        }

        .nav-link.text-danger {
            color: #dc2626;
        }

        .nav-link.text-danger:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Content Area */
        .content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
        }

        /* Job Cards */
        .job-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.65, 0, 0.35, 1);
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.12);
        }

        .job-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            object-position: center;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            display: block;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .badge {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
            border-radius: 6px;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .btn-warning {
            background-color: #ff9f0a;
            border-color: #ff9f0a;
            color: white;
        }

        .btn-warning:hover {
            background-color: #ff8c00;
            border-color: #ff8c00;
            color: white;
        }

        .btn-danger {
            background-color: #ff3b30;
            border-color: #ff3b30;
        }

        .btn-danger:hover {
            background-color: #ff2d55;
            border-color: #ff2d55;
        }

        .alert {
            border-radius: 12px;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
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
                    <a class="nav-link" href="jobs.php">
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
                    <a class="nav-link active" href="my_job_posts.php">
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

        <div class="content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">My Job Posts</h2>
                    <a href="create_job.php" class="btn btn-primary">
                        <i class="cil-plus me-2"></i>Create New Job
                    </a>
                </div>

                <?php if ($my_jobs_result->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($job = $my_jobs_result->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="job-card">
                                    <?php if (!empty($job['job_image'])): ?>
                                        <img src="<?= htmlspecialchars($job['job_image']) ?>" 
                                             class="job-image" 
                                             alt="Job Image">
                                    <?php endif; ?>
                                    
                                    <div class="card-body p-4">
                                        <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                                        <p class="text-gray-600 mb-4">
                                            <?= substr(htmlspecialchars($job['description']), 0, 150) ?>...
                                        </p>
                                        
                                        <div class="mb-4 text-gray-700">
                                            <p class="mb-2">
                                                <span class="font-semibold">Job Type:</span> 
                                                <?= htmlspecialchars($job['job_type']) ?>
                                            </p>
                                            <p class="mb-2">
                                                <span class="font-semibold">Budget:</span> 
                                                ₹<?= number_format($job['reward'], 2) ?>
                                            </p>
                                            <p class="mb-2">
                                                <span class="font-semibold">Start Date:</span> 
                                                <?= date('M d, Y', strtotime($job['start_date'])) ?>
                                            </p>
                                            <p class="mb-2">
                                                <span class="font-semibold">End Date:</span> 
                                                <?= date('M d, Y', strtotime($job['end_date'])) ?>
                                            </p>
                                        </div>

                                        <?php 
                                        $tags = json_decode($job['tags'], true);
                                        if (!empty($tags)): 
                                        ?>
                                            <div class="mb-4">
                                                <h6 class="font-semibold mb-2">Tags:</h6>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php foreach ($tags as $tag): ?>
                                                        <span class="badge bg-gray-200 text-gray-700">
                                                            <?= htmlspecialchars(str_replace('-', ' ', $tag)) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
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
                                                       <i class="cil-file me-2"></i>View File
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="flex gap-2 mt-4">
                                            <a href="edit_job.php?job_id=<?= $job['id'] ?>" 
                                               class="btn btn-warning btn-sm">
                                               <i class="cil-pencil me-1"></i>Edit
                                            </a>
                                            <a href="delete_job.php?job_id=<?= $job['id'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this job?');">
                                               <i class="cil-trash me-1"></i>Delete
                                            </a>
                                            <?php if($job['application_status'] == 'accepted'): ?>
                                                <a href="chat.php?job_id=<?= $job['id'] ?>&client_id=<?= $job['applicant_id'] ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="cil-chat-bubble me-1"></i>Chat with Client
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <p class="text-lg mb-4">You haven't created any job posts yet.</p>
                        <a href="create_job.php" class="btn btn-primary">
                            <i class="cil-plus me-2"></i>Create Your First Job
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>