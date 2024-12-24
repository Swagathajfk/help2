<?php
include('db_connect.php');
session_start();

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$job_id = $data['job_id'] ?? null;
$action = $data['action'] ?? null;

if (!$job_id || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Update job status - Changed 'approved' to 'open' for approve action
    $status = ($action === 'approve') ? 'open' : 'rejected';
    $sql = "UPDATE jobs SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $job_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update job status");
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Job successfully {$action}d"]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();