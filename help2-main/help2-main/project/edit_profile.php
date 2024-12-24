<?php
include('db_connect.php'); // DB connection
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data from the database
$sql = "SELECT email, first_name, surname, mobile, address1, address2, postcode, state, area, country, education FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    $first_name = $_POST['first_name'];
    $surname = $_POST['surname'];
    $mobile = $_POST['mobile'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $postcode = $_POST['postcode'];
    $state = $_POST['state'];
    $area = $_POST['area'];
    $country = $_POST['country'];
    $education = $_POST['education'];

    // Update the user's profile
    if ($password) {
        $update_sql = "UPDATE users SET email = ?, password = ?, first_name = ?, surname = ?, mobile = ?, address1 = ?, address2 = ?, postcode = ?, state = ?, area = ?, country = ?, education = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('ssssssssssssi', $email, $password, $first_name, $surname, $mobile, $address1, $address2, $postcode, $state, $area, $country, $education, $user_id);
    } else {
        $update_sql = "UPDATE users SET email = ?, first_name = ?, surname = ?, mobile = ?, address1 = ?, address2 = ?, postcode = ?, state = ?, area = ?, country = ?, education = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('sssssssssssi', $email, $first_name, $surname, $mobile, $address1, $address2, $postcode, $state, $area, $country, $education, $user_id);
    }

    if ($stmt->execute()) {
        $success_message = "Profile updated successfully.";
        $_SESSION['user_name'] = $first_name . ' ' . $surname; // Syncing user name for dashboard
    } else {
        $error_message = "Failed to update profile.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/coreui-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-bg: #1c1c1e;
            --sidebar-hover: #2c2c2e;
            --card-bg: #ffffff;
            --text-primary: #000000;
            --text-secondary: #6e6e73;
            --accent-blue: #0071e3;
            --shadow-sm: rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: #f5f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', sans-serif;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.01em;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            width: 280px;
            min-height: 100vh;
            position: fixed;
            padding: 1.5rem 1rem;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 0.9rem 1.25rem;
            margin: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s cubic-bezier(0.65, 0, 0.35, 1);
        }

        .nav-link:hover {
            background: var(--sidebar-hover);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: var(--accent-blue);
            color: white;
            font-weight: 600;
        }

        .nav-link i {
            font-size: 1.1rem;
            opacity: 0.75;
        }

        .content {
            background: #f5f7fa;
            min-height: 100vh;
            margin-left: 280px; /* Added to account for sidebar */
            width: calc(100% - 280px); /* Added to ensure content doesn't overlap sidebar */
            display: flex;
            justify-content: center; /* Center horizontally */
            align-items: flex-start; /* Align to top */
            padding: 2rem;
        }

        .profile-container {
            max-width: 800px; /* Reduced from 900px */
            width: 100%;
            margin: 0 auto;
            padding: 1rem; /* Reduced padding */
        }

        .profile-header {
            background: white;
            border-radius: 16px;
            padding: 1.5rem; /* Reduced padding */
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .profile-image {
            width: 100px; /* Reduced from 120px */
            height: 100px; /* Reduced from 120px */
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.05);
        }

        .profile-info h4 {
            font-size: 1.3rem; /* Reduced from 1.5rem */
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .profile-info p {
            color: var(--text-secondary);
            font-size: 0.9rem; /* Reduced from 0.95rem */
        }

        .profile-form {
            background: white;
            border-radius: 16px;
            padding: 1.5rem; /* Reduced from 2rem */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .form-section {
            margin-bottom: 1.5rem; /* Reduced from 2rem */
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 1rem; /* Reduced from 1.1rem */
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.2rem;
        }

        .form-control {
            background-color: #f8f9fa;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 10px;
            padding: 0.7rem 0.9rem; /* Reduced padding */
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.1);
            transform: translateY(-1px);
        }

        .form-label {
            font-size: 0.9rem; /* Added smaller font size */
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.4rem;
        }

        .btn-update {
            background: linear-gradient(135deg, var(--accent-blue), #40a9ff);
            color: white;
            padding: 0.7rem 2rem; /* Reduced padding */
            border-radius: 10px;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 113, 227, 0.2);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.2rem; /* Reduced padding */
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #f0fdf4;
            color: #15803d;
        }

        .alert-danger {
            background: #fef2f2;
            color: #b91c1c;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Updated Sidebar -->
        <div class="sidebar">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="freelancer_dashboard.php">
                        <i class="cil-speedometer"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="create_job.php">
                        <i class="cil-plus"></i> Create Job
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="jobs.php">
                        <i class="cil-briefcase"></i> Job Listings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_applications.php">
                        <i class="cil-task"></i> My Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="edit_profile.php">
                        <i class="cil-user"></i> My Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_job_posts.php">
                        <i class="cil-folder"></i> My Job Posts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="notifications.php">
                        <i class="cil-bell"></i> Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php">
                        <i class="cil-account-logout"></i> Logout
                    </a>
                </li>
            </ul>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="profile-container">
                <div class="profile-header">
                    <img src="https://st3.depositphotos.com/15648834/17930/v/600/depositphotos_179308454-stock-illustration-unknown-person-silhouette-glasses-profile.jpg" 
                         alt="Profile Picture" 
                         class="profile-image">
                    <div class="profile-info">
                        <h4><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></h4>
                        <p><?= htmlspecialchars($user_data['email']) ?></p>
                    </div>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php elseif (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <div class="profile-form">
                    <form method="POST">
                        <div class="form-section">
                            <h3 class="section-title">Personal Information</h3>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user_data['first_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Surname</label>
                                    <input type="text" name="surname" class="form-control" value="<?= htmlspecialchars($user_data['surname']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Contact Details</h3>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user_data['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($user_data['mobile']) ?>" required>
                                    </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Address</h3>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Address 1</label>
                                    <input type="text" name="address1" class="form-control" value="<?= htmlspecialchars($user_data['address1']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Address 2</label>
                                    <input type="text" name="address2" class="form-control" value="<?= htmlspecialchars($user_data['address2']) ?>">
                                </div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Postcode</label>
                                    <input type="text" name="postcode" class="form-control" value="<?= htmlspecialchars($user_data['postcode']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($user_data['state']) ?>">
                                </div>
                            </div>
                            <div class="row g-4 mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Area</label>
                                    <input type="text" name="area" class="form-control" value="<?= htmlspecialchars($user_data['area']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Country</label>
                                    <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($user_data['country']) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title">Education</h3>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Education</label>
                                    <input type="text" name="education" class="form-control" value="<?= htmlspecialchars($user_data['education']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-update">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>