<?php
include('db_connect.php');
session_start();

// Check if the user is logged in as a freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'freelancer') {
    header("Location: login.php");
    exit;
}

// Check if job ID is provided
if (!isset($_GET['job_id'])) {
    header("Location: my_job_posts.php");
    exit;
}

$job_id = $_GET['job_id'];
$freelancer_id = $_SESSION['user_id'];

// Fetch job details from the database
$sql = "SELECT * FROM jobs WHERE id = ? AND freelancer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $job_id, $freelancer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: my_job_posts.php");
    exit;
}

$job = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $job_type = $_POST['job_type'];
    $reward = floatval($_POST['reward']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $deadline = $end_date;

    // Handle tags
    $tags = isset($_POST['tags']) ? json_encode(array_map('trim', explode(',', $_POST['tags']))) : null;

    // Handle file uploads
    $existing_files = json_decode($job['files'], true) ?: [];
    $new_files = [];

    if (!empty($_FILES['files']['name'][0])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['files']['name'] as $key => $filename) {
            if ($_FILES['files']['error'][$key] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['files']['tmp_name'][$key];
                $unique_filename = uniqid() . '_' . $filename;
                $upload_path = $upload_dir . $unique_filename;
                
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $new_files[] = $upload_path;
                }
            }
        }
    }

    // Merge existing and new files
    $all_files = array_merge($existing_files, $new_files);
    $files_json = json_encode($all_files);

    // Update job in the database
    $update_sql = "UPDATE jobs SET 
        title = ?, 
        description = ?, 
        job_type = ?, 
        reward = ?, 
        deadline = ?, 
        start_date = ?, 
        end_date = ?, 
        tags = ?, 
        files = ? 
        WHERE id = ? AND freelancer_id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param(
        'ssssdssssii', 
        $title, 
        $description, 
        $job_type, 
        $reward, 
        $deadline,
        $start_date, 
        $end_date, 
        $tags, 
        $files_json, 
        $job_id, 
        $freelancer_id
    );

    if ($update_stmt->execute()) {
        header("Location: my_job_posts.php");
        exit;
    } else {
        $error = "Error updating job: " . $update_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Freelancing Marketplace</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #FAFAFA; }
        .form-container { max-width: 600px; margin: 20px auto; padding: 20px; background-color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="mb-4">Edit Job</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Job Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($job['title']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($job['description']) ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select" id="job_type" name="job_type" required>
                        <option value="Remote" <?= $job['job_type'] == 'Remote' ? 'selected' : '' ?>>Remote</option>
                        <option value="On-site" <?= $job['job_type'] == 'On-site' ? 'selected' : '' ?>>On-site</option>
                        <option value="Contract" <?= $job['job_type'] == 'Contract' ? 'selected' : '' ?>>Contract</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="reward" class="form-label">Reward (₹)</label>
                    <input type="number" class="form-control" id="reward" name="reward" value="<?= htmlspecialchars($job['reward']) ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($job['start_date']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($job['end_date']) ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="tags" class="form-label">Tags (comma-separated)</label>
                    <input type="text" class="form-control" id="tags" name="tags" 
                        value="<?= $job['tags'] ? htmlspecialchars(implode(',', json_decode($job['tags'], true))) : '' ?>">
                </div>
                
                <div class="mb-3">
                    <label for="files" class="form-label">Additional Files</label>
                    <input type="file" class="form-control" id="files" name="files[]" multiple>
                    
                    <?php 
                    $existing_files = json_decode($job['files'], true);
                    if (!empty($existing_files)): 
                    ?>
                    <div class="mt-2">
                        <strong>Existing Files:</strong>
                        <?php foreach ($existing_files as $file): ?>
                            <a href="<?= htmlspecialchars($file) ?>" target="_blank" class="d-block">
                                <?= basename(htmlspecialchars($file)) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Job</button>
                <a href="my_job_posts.php" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>