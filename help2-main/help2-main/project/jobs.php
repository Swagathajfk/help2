<?php
include('db_connect.php'); // Include DB connection
session_start();

// Check if the user is logged in (freelancer or client)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch all jobs from the database along with job files and edits
$sql = "SELECT jobs.id, jobs.title, jobs.description, jobs.job_type, jobs.reward, jobs.start_date, jobs.end_date, users.email AS freelancer_email 
        FROM jobs 
        JOIN users ON jobs.freelancer_id = users.id";
$result = $conn->query($sql);

// Fetch job files separately
$job_files_sql = "SELECT job_id, file_path FROM job_files";
$job_files_result = $conn->query($job_files_sql);

$job_edits_sql = "SELECT job_id, updated_description, updated_at FROM job_edits";
$job_edits_result = $conn->query($job_edits_sql);

// Prepare arrays to store the job files and job edits
$job_files = [];
$job_edits = [];

if ($job_files_result->num_rows > 0) {
    while ($file_row = $job_files_result->fetch_assoc()) {
        $job_files[$file_row['job_id']][] = $file_row['file_path'];
    }
}

if ($job_edits_result->num_rows > 0) {
    while ($edit_row = $job_edits_result->fetch_assoc()) {
        $job_edits[$edit_row['job_id']][] = [
            'updated_description' => $edit_row['updated_description'],
            'updated_at' => $edit_row['updated_at']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/coreui@4.1.0/dist/css/coreui.min.css" rel="stylesheet">
    
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

        /* Sidebar styling */
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
            color: #ff453a;
        }

        .nav-link.text-danger:hover {
            background: rgba(255, 69, 58, 0.1);
        }

        /* Content area styling */
        .content {
            flex: 1;
            margin-left: 280px;
            background: #f5f5f7;
            padding: 2rem;
            min-height: 100vh;
        }

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* Page header styling */
        .page-header {
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .page-header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1d1d1f;
            margin: 0;
        }

        /* Job cards grid */
        .job-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .job-card-header {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .job-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 0.5rem;
        }

        .job-type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            background: #e8f3ff;
            color: #0071e3;
        }

        .job-card-body {
            padding: 1.25rem;
        }

        .job-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .job-info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .job-info-label {
            font-size: 0.8rem;
            color: #6e6e73;
            font-weight: 500;
        }

        .job-info-value {
            font-size: 0.95rem;
            color: #1d1d1f;
            font-weight: 500;
        }

        .job-description {
            font-size: 0.95rem;
            color: #424245;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .job-card-footer {
            padding: 1rem 1.25rem;
            background: #f5f5f7;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .job-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-view {
            background: #0071e3;
            color: white;
            border: none;
        }

        .btn-view:hover {
            background: #0077ED;
        }

        .btn-edit {
            background: #ffd60a;
            color: #1d1d1f;
            border: none;
        }

        .btn-edit:hover {
            background: #ffdb33;
        }

        .btn-delete {
            background: #ff453a;
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background: #ff5b52;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            .content {
                margin-left: 0;
            }
            .job-grid {
                grid-template-columns: 1fr;
            }
            .container-fluid {
                padding: 1rem;
            }
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

        <div class="content">
            <div class="container-fluid">
                <div class="page-header">
                    <h2>Job Listings</h2>
                </div>
                
                <div class="job-grid">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="job-card">
                                <div class="job-card-header">
                                    <h3 class="job-title"><?= htmlspecialchars($row['title']) ?></h3>
                                    <span class="job-type-badge"><?= htmlspecialchars($row['job_type']) ?></span>
                                </div>
                                
                                <div class="job-card-body">
                                    <div class="job-info-grid">
                                        <div class="job-info-item">
                                            <span class="job-info-label">Reward</span>
                                            <span class="job-info-value">₹<?= htmlspecialchars($row['reward']) ?></span>
                                        </div>
                                        <div class="job-info-item">
                                            <span class="job-info-label">Posted By</span>
                                            <span class="job-info-value"><?= htmlspecialchars($row['freelancer_email']) ?></span>
                                        </div>
                                        <div class="job-info-item">
                                            <span class="job-info-label">Start Date</span>
                                            <span class="job-info-value"><?= htmlspecialchars($row['start_date']) ?></span>
                                        </div>
                                        <div class="job-info-item">
                                            <span class="job-info-label">End Date</span>
                                            <span class="job-info-value"><?= htmlspecialchars($row['end_date']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <p class="job-description"><?= htmlspecialchars($row['description']) ?></p>
                                </div>
                                
                                <div class="job-card-footer">
                                    <div class="job-actions">
                                        <a href="job_details.php?job_id=<?= $row['id'] ?>" class="btn btn-view">View Details</a>
                                        <?php if ($_SESSION['role'] == 'freelancer' && isset($row['freelancer_id']) && $_SESSION['user_id'] == $row['freelancer_id']): ?>
                                            <a href="edit_job.php?job_id=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
                                            <a href="delete_job.php?job_id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this job?');">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
            <?php else: ?>
                <div class="no-jobs-message">
                    <p>No jobs found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>