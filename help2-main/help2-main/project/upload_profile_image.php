<?php
// upload_profile_image.php
include('db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_image'])) {
    $user_id = $_SESSION['user_id'];
    $target_dir = "uploads/profile_images/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $new_filename = $user_id . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check file type
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (!in_array($file_extension, $allowed_types)) {
        header("Location: client_profile.php?error=Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
        exit;
    }
    
    // Check file size (5MB max)
    if ($_FILES["profile_image"]["size"] > 5000000) {
        header("Location: client_profile.php?error=File is too large. Maximum size is 5MB.");
        exit;
    }

    // Delete old profile image if exists
    $stmt = $conn->prepare("SELECT profile_image FROM clients WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
        unlink($user['profile_image']);
    }
    
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        // Update database with new image path
        $stmt = $conn->prepare("UPDATE clients SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $user_id);
        
        if ($stmt->execute()) {
            header("Location: client_profile.php?success=Profile image updated successfully");
        } else {
            header("Location: client_profile.php?error=Failed to update profile image in database");
        }
    } else {
        header("Location: client_profile.php?error=Failed to upload image");
    }
} else {
    header("Location: client_profile.php");
}
?>