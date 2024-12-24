<?php
// filepath: /project/api/send_message.php
include('../db_connect.php');
session_start();

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Get POST data
    $job_id = $_POST['job_id'] ?? null;
    $sender_id = $_POST['sender_id'] ?? null;
    $receiver_id = $_POST['receiver_id'] ?? null;
    $message = trim($_POST['message'] ?? '');

    // Validate required fields
    if (!$job_id || !$sender_id || !$receiver_id || !$message) {
        throw new Exception('Missing required fields');
    }

    // Handle file attachment if present
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $upload_dir = '../uploads/chat_attachments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $unique_filename;

        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            throw new Exception('Invalid file type');
        }

        // Check file size (5MB max)
        if ($_FILES['attachment']['size'] > 5242880) {
            throw new Exception('File too large. Maximum size is 5MB');
        }

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
            $attachment_path = 'uploads/chat_attachments/' . $unique_filename;
        } else {
            throw new Exception('Failed to upload file');
        }
    }

    // Insert message into database
    $sql = "INSERT INTO messages (job_id, sender_id, receiver_id, message, attachment_path) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiss', $job_id, $sender_id, $receiver_id, $message, $attachment_path);

    if (!$stmt->execute()) {
        throw new Exception('Failed to send message');
    }

    // Add notification for receiver
    $notify_sql = "INSERT INTO notifications (user_id, message, user_type) 
                   SELECT ?, 
                          CONCAT('New message received for job: ', title),
                          CASE 
                            WHEN ? = freelancer_id THEN 'freelancer'
                            ELSE 'client'
                          END
                   FROM jobs 
                   WHERE id = ?";
    $notify_stmt = $conn->prepare($notify_sql);
    $notify_stmt->bind_param('iii', $receiver_id, $receiver_id, $job_id);
    $notify_stmt->execute();

    echo json_encode([
        'success' => true, 
        'message' => 'Message sent successfully',
        'attachment_path' => $attachment_path
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>