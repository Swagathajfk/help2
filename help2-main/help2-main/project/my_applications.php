<?php
include('db_connect.php');
session_start();

// Check freelancer login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'freelancer') {
    header("Location: login.php");
    exit;
}

// Fix the SQL query for freelancer viewing applications
$sql = "SELECT ja.*, 
        j.title as job_title, 
        j.description as job_description,
        u.email as client_email, 
        u.first_name, 
        u.surname,
        c.profile_image,
        c.company_name
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON ja.client_id = u.id    /* Changed: Join with client who applied */
        LEFT JOIN clients c ON u.id = c.id
        WHERE j.freelancer_id = ?              /* Changed: Show applications for freelancer's jobs */
        ORDER BY ja.applied_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result();

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$applications = $stmt->get_result();

// Handle status updates
if(isset($_POST['update_status'])) {
    $app_id = $_POST['application_id'];
    $status = $_POST['status'];
    $response = $_POST['response'];

    $update_sql = "UPDATE job_applications 
                   SET status = ?, 
                       freelancer_response = ? 
                   WHERE application_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $status, $response, $app_id);
    
    if($update_stmt->execute()) {
        // Add notification for client
        $notify_sql = "INSERT INTO notifications (user_id, message, user_type)
                      SELECT client_id,
                             CONCAT('Your application has been ', ?, ' for job: ',
                                    (SELECT title FROM jobs WHERE id = job_id)),
                             'client'
                      FROM job_applications WHERE application_id = ?";
        $notify_stmt = $conn->prepare($notify_sql);
        $notify_stmt->bind_param("si", $status, $app_id);
        $notify_stmt->execute();
        
        header("Location: my_applications.php?msg=updated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        /* Sidebar styling - matching other pages */
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

        .content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: #ffffff;
        }

        .nav-link.text-danger {
            color: #ff453a;
        }

        .nav-link.text-danger:hover {
            background: rgba(255, 69, 58, 0.1);
        }

        .application-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .status-badge {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-weight: 500;
        }

        .client-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background-color: var(--light-accent);
            border-radius: 10px;
        }

        .client-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 1rem;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .status-pending { background-color: #ffc107; color: #000; }
        .status-reviewing { background-color: #17a2b8; color: #fff; }
        .status-accepted { background-color: #28a745; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }

        /* Enhanced Content Area Styling */
        .content {
            background: #f5f7fa;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        /* Application Cards Grid */
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .application-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .application-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(to right, #f8f9ff, #ffffff);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .client-image {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .client-details {
            flex: 1;
        }

        .client-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .client-company {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .job-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .status-pending { 
            background: #fff7ed; 
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .status-reviewing { 
            background: #eff6ff; 
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .status-accepted { 
            background: #f0fdf4; 
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .status-rejected { 
            background: #fef2f2; 
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .info-grid {
            display: grid;
            gap: 1rem;
            margin: 1rem 0;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .info-value {
            color: var(--text-primary);
            line-height: 1.5;
        }

        .card-actions {
            padding: 1rem 1.5rem;
            background: #fafafa;
            border-top: 1px solid var(--border);
        }

        .btn-review {
            width: 100%;
            background: var(--accent-blue);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-review:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 113, 227, 0.2);
        }

        /* Review Modal Enhancements */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(to right, #f8f9ff, #ffffff);
            border-bottom: 1px solid var(--border);
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            background: #fafafa;
            border-top: 1px solid var(--border);
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
                    <a class="nav-link" href="jobs.php">
                        <i class="cil-briefcase"></i> Job Listings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="my_applications.php">
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
                <li class="nav-item mt-auto">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="cil-account-logout"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content">
            <div class="container">
                <div class="page-header">
                    <h2 class="page-title">Review Applications</h2>
                </div>

                <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Application status updated successfully
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="applications-grid">
                    <?php if($applications->num_rows > 0): ?>
                        <?php while($app = $applications->fetch_assoc()): ?>
                            <div class="application-card">
                                <div class="card-header">
                                    <div class="client-info">
                                        <img src="<?= !empty($app['profile_image']) ? htmlspecialchars($app['profile_image']) : 'uploads/default-avatar.png' ?>" 
                                             class="client-image" alt="Client Profile">
                                        <div class="client-details">
                                            <div class="client-name"><?= htmlspecialchars($app['first_name'] . ' ' . $app['surname']) ?></div>
                                            <div class="client-company"><?= htmlspecialchars($app['company_name']) ?></div>
                                        </div>
                                    </div>
                                    <h3 class="job-title"><?= htmlspecialchars($app['job_title']) ?></h3>
                                    <span class="status-badge status-<?= $app['status'] ?>">
                                        <?= ucfirst($app['status']) ?>
                                    </span>
                                </div>

                                <div class="card-body">
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <span class="info-label">Applied On</span>
                                            <span class="info-value"><?= date('M d, Y', strtotime($app['applied_date'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Why hire</span>
                                            <span class="info-value"><?= htmlspecialchars($app['why_hire']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Contact</span>
                                            <span class="info-value"><?= htmlspecialchars($app['contact_info']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Meeting Preference</span>
                                            <span class="info-value"><?= htmlspecialchars($app['meeting_preference']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <?php if($app['status'] == 'pending' || $app['status'] == 'reviewing'): ?>
                                    <div class="card-actions">
                                        <button type="button" class="btn-review" 
                                                onclick="openReviewModal(
                                                    <?= $app['application_id'] ?>, 
                                                    '<?= htmlspecialchars($app['job_title']) ?>',
                                                    '<?= htmlspecialchars($app['why_hire']) ?>',
                                                    '<?= htmlspecialchars($app['contact_info']) ?>',
                                                    '<?= htmlspecialchars($app['meeting_preference']) ?>'
                                                )">
                                            <?= $app['status'] == 'pending' ? 'Review Application' : 'Update Review' ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No applications to review at the moment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Application: <span id="jobTitle"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="application_id" id="modalAppId">
                        
                        <div class="mb-3">
                            <label class="form-label">Client's Message</label>
                            <div class="client-message p-3 bg-light rounded" id="clientMessage"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Information</label>
                            <div class="contact-info p-3 bg-light rounded" id="contactInfo"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meeting Preference</label>
                            <div class="meeting-pref p-3 bg-light rounded" id="meetingPref"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Your Response</label>
                            <textarea name="response" class="form-control" rows="4" required 
                                    placeholder="Provide feedback or additional information..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Decision</label>
                            <select name="status" class="form-control" required>
                                <option value="reviewing">Mark as Reviewing</option>
                                <option value="accepted">Accept Application</option>
                                <option value="rejected">Reject Application</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Submit Decision</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openReviewModal(appId, jobTitle, whyHire, contactInfo, meetingPref) {
            document.getElementById('modalAppId').value = appId;
            document.getElementById('jobTitle').textContent = jobTitle;
            document.getElementById('clientMessage').textContent = whyHire || 'No message provided';
            document.getElementById('contactInfo').textContent = contactInfo || 'No contact information provided';
            document.getElementById('meetingPref').textContent = meetingPref || 'No preference specified';
            
            new bootstrap.Modal(document.getElementById('reviewModal')).show();
        }
    </script>
</body>
</html>