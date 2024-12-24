<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First delete related records from job_applications
    $conn->query("DELETE FROM job_applications WHERE client_id = $user_id");
    
    // Delete from notifications
    $conn->query("DELETE FROM notifications WHERE user_id = $user_id");
    
    // Delete from messages
    $conn->query("DELETE FROM messages WHERE sender_id = $user_id OR receiver_id = $user_id");
    
    // Delete from clients/freelancers tables
    $conn->query("DELETE FROM clients WHERE id = $user_id");
    $conn->query("DELETE FROM freelancers WHERE id = $user_id");
    
    // Delete from jobs
    $conn->query("UPDATE jobs SET assigned_to = NULL WHERE assigned_to = $user_id");
    $conn->query("DELETE FROM jobs WHERE freelancer_id = $user_id");

    // Finally delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete user");
    }

    // If we got here, commit the transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);

} catch (Exception $e) {
    // Something went wrong, rollback
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();