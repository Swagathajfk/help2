<?php
include('../db_connect.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$job_id = $_GET['job_id'] ?? null;

if (!$job_id) {
    echo json_encode(['error' => 'Missing job ID']);
    exit;
}

try {
    // Mark messages as read
    $update_sql = "UPDATE messages 
                   SET is_read = 1 
                   WHERE job_id = ? 
                   AND receiver_id = ? 
                   AND is_read = 0";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ii', $job_id, $_SESSION['user_id']);
    $update_stmt->execute();

    // Fetch messages
    $sql = "SELECT m.*, 
            CONCAT(u.first_name, ' ', u.surname) as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.job_id = ?
            ORDER BY m.created_at ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'created_at' => $row['created_at'],
            'attachment_path' => $row['attachment_path']
        ];
    }
    
    echo json_encode($messages);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
