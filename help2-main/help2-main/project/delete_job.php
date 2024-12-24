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

// Delete job from the database
$sql = "DELETE FROM jobs WHERE id = ? AND freelancer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $job_id, $freelancer_id);

if ($stmt->execute()) {
    // Optional: Delete associated files
    $files_sql = "SELECT files FROM jobs WHERE id = ? AND freelancer_id = ?";
    $files_stmt = $conn->prepare($files_sql);
    $files_stmt->bind_param('ii', $job_id, $freelancer_id);
    $files_stmt->execute();
    $result = $files_stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $files = json_decode($row['files'], true);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    header("Location: my_job_posts.php?delete=success");
    exit;
} else {
    header("Location: my_job_posts.php?delete=error");
    exit;
}
?>