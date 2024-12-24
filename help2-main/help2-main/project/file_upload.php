<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$response = ['success' => false, 'message' => '', 'files' => []];

try {
    if (!empty($_FILES['files']['name'][0])) {
        $upload_dir = 'uploads/progress_files/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $file_name = uniqid() . '_' . $_FILES['files']['name'][$key];
            $file_path = $upload_dir . $file_name;
            
            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            
            if (!in_array($_FILES['files']['type'][$key], $allowed_types)) {
                throw new Exception('Invalid file type. Only images, PDFs and Word documents are allowed.');
            }

            // Check file size (5MB max)
            if ($_FILES['files']['size'][$key] > 5242880) {
                throw new Exception('File too large. Maximum size is 5MB.');
            }

            if (move_uploaded_file($tmp_name, $file_path)) {
                $response['files'][] = $file_path;
            } else {
                throw new Exception('Failed to upload file: ' . $_FILES['files']['name'][$key]);
            }
        }

        $response['success'] = true;
        $response['message'] = 'Files uploaded successfully';
    } else {
        throw new Exception('No files uploaded');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>