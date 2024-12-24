<?php
include('db_connect.php');
session_start();

if (isset($_SESSION['user_id'])) {
    // Check for new unread notifications in last 30 seconds
    $sql = "SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? 
            AND is_read = 0 
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'new_notifications' => $result['count'] > 0
    ]);
}
?>