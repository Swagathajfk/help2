<?php
function validateJobFile($file) {
    $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    return ['valid' => true];
}
