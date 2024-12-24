<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_message = '';
$error_message = '';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $status = $_POST['status'];
    $role = $_POST['role'];
    
    try {
        $update_sql = "UPDATE users SET email = ?, status = ?, role = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $email, $status, $role, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "User updated successfully!";
            // Refresh user data
            $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Error updating user!";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Edit User</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="suspended" <?= $user['status'] == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role">
                                    <option value="client" <?= $user['role'] == 'client' ? 'selected' : '' ?>>Client</option>
                                    <option value="freelancer" <?= $user['role'] == 'freelancer' ? 'selected' : '' ?>>Freelancer</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">Update User</button>
                                <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>