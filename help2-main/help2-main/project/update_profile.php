<?php
// update_profile.php
include('db_connect.php');
session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Sanitize and validate inputs
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone_number = filter_var($_POST['phone_number'], FILTER_SANITIZE_STRING);
    $company_name = filter_var($_POST['company_name'], FILTER_SANITIZE_STRING);
    $industry = filter_var($_POST['industry'], FILTER_SANITIZE_STRING);
    $location = filter_var($_POST['location'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if email already exists for another user
    $stmt = $conn->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception("Email already in use by another account");
    }

    // Prepare statement to update user information
    $stmt = $conn->prepare("UPDATE clients SET 
        first_name = ?,
        last_name = ?,
        email = ?,
        phone_number = ?,
        company_name = ?,
        industry = ?,
        location = ?,
        description = ?
        WHERE id = ?");

    $stmt->bind_param("ssssssssi",
        $first_name,
        $last_name,
        $email,
        $phone_number,
        $company_name,
        $industry,
        $location,
        $description,
        $user_id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        throw new Exception("Failed to update profile");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>