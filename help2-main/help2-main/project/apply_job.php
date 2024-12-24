<?php
include('db_connect.php');
session_start();

// Check if user is logged in as client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Validate required fields
if (!isset($_POST['job_id'], $_POST['why_hire'], $_POST['contact_info'], $_POST['meeting_preference'])) {
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

try {
    // Check if already applied - Fix: Changed id to application_id
    $check_sql = "SELECT application_id FROM job_applications WHERE job_id = ? AND client_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $_POST['job_id'], $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        die(json_encode(['success' => false, 'message' => 'You have already applied for this job']));
    }

    // Check if job is still open
    $job_sql = "SELECT status FROM jobs WHERE id = ? AND status = 'open'";
    $job_stmt = $conn->prepare($job_sql);
    $job_stmt->bind_param("i", $_POST['job_id']);
    $job_stmt->execute();
    
    if ($job_stmt->get_result()->num_rows === 0) {
        die(json_encode(['success' => false, 'message' => 'This job is no longer available']));
    }

    // Insert application
    $sql = "INSERT INTO job_applications (job_id, client_id, why_hire, contact_info, meeting_preference, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisss", 
        $_POST['job_id'],
        $_SESSION['user_id'],
        $_POST['why_hire'],
        $_POST['contact_info'],
        $_POST['meeting_preference']
    );

    if ($stmt->execute()) {
        // Add notification for job owner
        $notify_sql = "INSERT INTO notifications (user_id, message, user_type) 
                      SELECT freelancer_id, 
                             CONCAT('New application received for job: ', title),
                             'freelancer'
                      FROM jobs 
                      WHERE id = ?";
        $notify_stmt = $conn->prepare($notify_sql);
        $notify_stmt->bind_param("i", $_POST['job_id']);
        $notify_stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully'
        ]);
    } else {
        throw new Exception('Failed to submit application');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}