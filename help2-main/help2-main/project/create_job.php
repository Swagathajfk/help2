<?php
include('db_connect.php');
session_start();

// Add this after session_start():
$upload_dir = 'uploads/job_files/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if the user is logged in as a freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'freelancer') {
    header("Location: login.php");
    exit;
}

// Predefined job tags
$job_tags = [
    'web-development', 'graphic-design', 'content-writing', 
    'digital-marketing', 'data-analysis', 'mobile-app-development', 
    'video-editing', 'translation', 'consulting'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $custom_job_type = trim($_POST['custom_job_type']);
    $budget = floatval($_POST['budget']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $freelancer_id = $_SESSION['user_id']; 
    $selected_tags = $_POST['tags'] ?? [];

    // Validate inputs
    $errors = [];
    if (empty($title)) $errors[] = "Job title is required.";
    if (empty($description)) $errors[] = "Job description is required.";
    if (empty($custom_job_type)) $errors[] = "Job type is required.";
    if ($budget <= 0) $errors[] = "Invalid budget amount.";
    if (empty($start_date) || empty($end_date)) $errors[] = "Start and end dates are required.";
    
    $start_datetime = new DateTime($start_date);
    $end_datetime = new DateTime($end_date);
    if ($start_datetime > $end_datetime) $errors[] = "Start date must be before end date.";

    // File upload handling for job image
    $job_image_path = null;
    if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] == 0) {
        $upload_dir = 'uploads/job_images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($_FILES['job_image']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '.' . $file_extension;
        $job_image_path = $upload_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['job_image']['tmp_name'], $job_image_path)) {
            $errors[] = "Error uploading job image.";
        }
    }

    // If no errors, proceed with job creation
    if (empty($errors)) {
        // Convert tags to JSON
        $tags_json = json_encode($selected_tags);

        // Insert the job into the database
        $sql = "INSERT INTO jobs (freelancer_id, title, description, job_type, reward, start_date, end_date, job_image, tags, client_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issdsssss', $freelancer_id, $title, $description, $custom_job_type, $budget, $start_date, $end_date, $job_image_path, $tags_json);

        if ($stmt->execute()) {
            $job_id = $conn->insert_id;
            
            // Handling additional file uploads
            if (isset($_FILES['job_files'])) {
                $job_files = [];
                foreach ($_FILES['job_files']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['job_files']['error'][$key] == 0) {
                        $file_name = basename($_FILES['job_files']['name'][$key]);
                        $file_path = 'uploads/job_files/' . uniqid() . '_' . $file_name;
                        
                        // Create directory if it doesn't exist
                        $file_dir = dirname($file_path);
                        if (!is_dir($file_dir)) {
                            mkdir($file_dir, 0755, true);
                        }

                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $job_files[] = $file_path;
                        }
                    }
                }

                // Update job with files if any
                if (!empty($job_files)) {
                    $job_files_json = json_encode($job_files);
                    $update_sql = "UPDATE jobs SET files = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('si', $job_files_json, $job_id);
                    $update_stmt->execute();
                }
            }
            
            header("Location: my_job_posts.php");
            exit;
        } else {
            $errors[] = "Error creating job.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job - Freelancing Marketplace</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/coreui@4.1.0/dist/css/coreui.min.css" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>

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

        .preview-image {
            max-width: 300px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
        }
        .card {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: none;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .content {
                margin-left: 0;
            }
        }

        /* Enhanced Form Controls */
        .form-control, .form-select {
            background-color: #f8f9fa;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-size: 0.95rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            color: #2c3e50;
        }

        .form-control:hover {
            background-color: #ffffff;
            border-color: rgba(0, 113, 227, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04);
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
            transform: translateY(-1px);
        }

        textarea.form-control {
            line-height: 1.6;
            background-image: linear-gradient(to bottom, #f8f9fa, #ffffff);
        }

        /* Style for number input */
        input[type="number"].form-control {
            background-color: #f8f9fa;
            border-right-width: 2px;
        }

        /* Multi-select enhancement */
        select[multiple].form-select {
            background-gradient: linear-gradient(to bottom, #f8f9fa, #ffffff);
            padding: 0.5rem;
        }

        select[multiple].form-select option {
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        select[multiple].form-select option:checked {
            background: rgba(0, 113, 227, 0.1);
            color: var(--accent-blue);
        }

        /* Date input specific styling */
        input[type="date"].form-control {
            position: relative;
            padding-right: 2.5rem;
        }

        .form-label {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.925rem;
        }

        /* Card Styling */
        .card {
            background: #ffffff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-body {
            padding: 2rem;
        }

        .card-title {
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 2rem;
        }

        /* File Upload Styling */
        .form-control[type="file"] {
            position: relative;
            padding: 1rem;
            background: linear-gradient(45deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px dashed #e2e8f0;
        }

        .form-control[type="file"]:hover {
            border-color: var(--accent-blue);
            background: linear-gradient(45deg, #f8f9fa 0%, #f0f7ff 100%);
        }

        /* Date Inputs */
        input[type="date"] {
            min-height: 45px;
        }

        /* Multiple Select */
        select[multiple] {
            min-height: 120px;
            background-image: none;
        }

        /* Submit Button */
        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), #40a9ff);
            border: none;
            padding: 0.8rem 2rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            box-shadow: 0 4px 12px rgba(0, 113, 227, 0.2);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 113, 227, 0.3);
            background: linear-gradient(135deg, #0062e3, #33a5ff);
        }

        /* Image Preview Enhancement */
        .preview-image {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .preview-image:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        /* Form Row Spacing */
        .row {
            margin-left: -1rem;
            margin-right: -1rem;
        }

        .row > [class*="col-"] {
            padding: 0 1rem;
        }

        /* Alert Enhancement */
        .alert-danger {
            background: #fff5f5;
            color: #dc2626;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 1rem 1.5rem;
        }

        /* Container Enhancement */
        .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Form Section Styling */
        .form-section {
            background: #ffffff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Form Group Styling */
        .form-group {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
        }

        .form-group:hover {
            background: #ffffff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .form-group .form-label {
            color: #374151;
            font-weight: 600;
            font-size: 0.925rem;
            margin-bottom: 0.75rem;
            display: block;
        }

        /* Card Styling Enhancement */
        .card {
            background: linear-gradient(to bottom, #ffffff, #f8f9fa);
            border: none;
            box-shadow: 
                0 4px 6px rgba(0, 0, 0, 0.05),
                0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 2rem;
        }

        /* Adjust the container padding */
        .container-fluid {
            max-width: 1000px;
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
                    <a class="nav-link active" href="create_job.php">
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
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Create a New Job</h2>
                        <form action="" method="post" enctype="multipart/form-data">
                            <!-- Basic Information Section -->
                            <div class="form-section">
                                <h3 class="form-section-title">Basic Information</h3>
                                <div class="form-group">
                                    <label for="title" class="form-label">Job Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                </div>
                            </div>

                            <!-- Job Details Section -->
                            <div class="form-section">
                                <h3 class="form-section-title">Job Details</h3>
                                <div class="form-group">
                                    <label for="custom_job_type" class="form-label">Job Type</label>
                                    <input type="text" class="form-control" id="custom_job_type" name="custom_job_type" required 
                                           placeholder="e.g., Remote, On-site, Contract">
                                </div>
                                <div class="form-group">
                                    <label for="budget" class="form-label">Reward (₹)</label>
                                    <input type="number" class="form-control" id="budget" name="budget" required min="0">
                                </div>
                            </div>

                            <!-- Schedule Section -->
                            <div class="form-section">
                                <h3 class="form-section-title">Schedule</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Media & Tags Section -->
                            <div class="form-section">
                                <h3 class="form-section-title">Media & Tags</h3>
                                <div class="form-group">
                                    <label for="job_image" class="form-label">Job Image (Optional)</label>
                                    <input type="file" class="form-control" id="job_image" name="job_image" accept="image/*">
                                    <img id="image-preview" class="preview-image mt-3" style="display:none;">
                                </div>
                                <div class="form-group">
                                    <label for="job_files" class="form-label">Additional Files</label>
                                    <input type="file" class="form-control" id="job_files" name="job_files[]" multiple>
                                </div>
                                <div class="form-group">
                                    <label for="tags" class="form-label">Job Tags</label>
                                    <select class="form-select" id="tags" name="tags[]" multiple>
                                        <?php foreach($job_tags as $tag): ?>
                                            <option value="<?= $tag ?>"><?= ucwords(str_replace('-', ' ', $tag)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary px-5">Create Job</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('job_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const previewImg = document.getElementById('image-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                previewImg.style.display = 'none';
            }
        });

        // Initialize multi-select for tags
        const tagsSelect = document.getElementById('tags');
        tagsSelect.multiple = true;
    </script>
</body>
</html>