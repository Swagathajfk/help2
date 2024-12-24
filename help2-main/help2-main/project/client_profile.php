<?php
// client_profile.php
include('db_connect.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'client') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Modify error checking
if (!$user) {
    // Create default client record if missing
    $create_sql = "INSERT INTO clients (id, first_name, last_name, email) 
                   SELECT id, first_name, surname, email 
                   FROM users WHERE id = ?";
    $create_stmt = $conn->prepare($create_sql);
    $create_stmt->bind_param("i", $user_id);
    $create_stmt->execute();
    
    // Fetch again
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0a0a0a;
            --secondary: #1a1a1a;
            --accent: #2a2a2a;
            --gold: #d4af37;
            --silver: #c0c0c0;
            --text: #333;
            --gradient-1: linear-gradient(135deg, var(--gold) 0%, var(--silver) 100%);
            --gradient-2: linear-gradient(45deg, var(--primary) 0%, var(--secondary) 100%);
        }

        body {
            background-color: var(--primary);
            font-family: 'Inter', -apple-system, sans-serif;
            color: var(--text);
            position: relative;
            overflow-x: hidden;
        }

        /* Updated Navbar Styling */
        .navbar {
            background: var(--secondary) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            padding: 0.5rem 0;
        }

        .navbar-brand {
            color: var(--gold) !important;
            font-size: 1.75rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar .container-fluid {
            padding: 0 1rem;
        }

        .navbar-nav {
            gap: 0.5rem;
        }

        .nav-link {
            color: var(--gold) !important;
            font-weight: 500;
            padding: 0.8rem 1.2rem !important;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(212, 175, 55, 0.1);
            transform: translateY(-2px);
        }

        /* Updated Search Input */
        .search-input {
            background: rgba(255,255,255,0.1) !important;
            border: 1px solid var(--gold) !important;
            color: white !important;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            min-width: 200px;
        }

        .search-input:focus {
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
            outline: none;
        }

        .search-input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .btn-outline-light {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            background: rgba(212, 175, 55, 0.1);
            border-color: var(--gold);
        }

        /* Logout Button */
        .navbar .btn-outline-light.rounded-pill {
            padding: 0.5rem 1.2rem;
            margin-left: 1rem;
            border-color: var(--gold);
        }

        .navbar .btn-outline-light.rounded-pill:hover {
            background: var(--gold);
            color: var(--primary);
        }

        /* Enhanced Profile Cards */
        .card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border-color: var(--gold);
        }

        .profile-header {
            background: var(--gradient-2);
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-1);
        }

        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            border-radius: 50%;
            padding: 5px;
            background: var(--gradient-1);
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
        }

        .image-upload-overlay {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--gold);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-upload-overlay:hover {
            transform: scale(1.1);
            background: var(--silver);
        }

        /* Form Styling */
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 10px;
            color: white;
            padding: 0.8rem;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--gold);
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
            color: white;
        }

        .form-label {
            color: var(--gold);
            font-weight: 500;
        }

        /* Company Info Section */
        .company-info {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
        }

        .info-label {
            color: var(--gold);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: white;
            font-size: 1rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        /* Button Styling */
        .btn-primary {
            background: var(--gradient-1);
            border: none;
            color: var(--primary);
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.2);
        }

        /* Search Input */
        .search-input {
            background: rgba(255,255,255,0.1) !important;
            border: 1px solid var(--gold) !important;
            color: white !important;
        }

        .search-input:focus {
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.2);
        }

        /* Animated Background */
        .background-animation {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 800px;
            height: 800px;
            transform: translate(-50%, -50%);
            background: linear-gradient(45deg, var(--gold) 0%, var(--primary) 25%, var(--gold) 50%, var(--primary) 75%, var(--gold) 100%);
            opacity: 0.03;
            filter: blur(50px);
            animation: rotate 20s linear infinite;
            z-index: 0;
            pointer-events: none;
        }

        @keyframes rotate {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Container for content */
        .container {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <!-- Add animated background -->
    <div class="background-animation"></div>

    <!-- Updated Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-diamond-fill me-2"></i>Kat
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="client_dashboard.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client_profile.php">
                            <i class="fas fa-user-tie"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pending_applications.php">
                            <i class="fas fa-clipboard-list"></i> Applied Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="client_notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
    <a class="nav-link" href="earnings.php">
        <i class="fas fa-wallet"></i> Earnings
    </a>
</li>
                </ul>
                <form class="d-flex me-3" action="search.php" method="GET">
                    <input class="form-control me-2 search-input" type="search" placeholder="Search" name="query">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <a href="logout.php" class="btn btn-outline-light rounded-pill">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Card -->
            <div class="col-md-4">
                <div class="card">
                    <div class="profile-header">
                        <div class="profile-image-container">
                            <img src="<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'uploads/profile_images/default-profile.png'; ?>" 
                                 alt="Profile Image" class="profile-image">
                            <label for="profile_image" class="image-upload-overlay">
                                <i class="fas fa-camera" style="color: white;"></i>
                            </label>
                        </div>
                        <h4 class="mt-3 mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="text-light mb-0"><?php echo htmlspecialchars($user['company_name']); ?></p>
                    </div>
                    <div class="card-body">
                        <form action="upload_profile_image.php" method="post" enctype="multipart/form-data" id="imageForm" style="display: none;">
                            <input type="file" name="profile_image" id="profile_image" accept="image/*" onchange="this.form.submit()">
                        </form>
                        <div class="company-info">
                            <div class="info-label"><i class="fas fa-industry"></i> Industry</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['industry'] ?? 'Not specified'); ?></div>
                            
                            <div class="info-label"><i class="fas fa-map-marker-alt"></i> Location</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['location'] ?? 'Not specified'); ?></div>
                            
                            <div class="info-label"><i class="fas fa-phone"></i> Contact</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'Not specified'); ?></div>

                            <div class="info-label"><i class="fas fa-clock"></i> Member Since</div>
                            <div class="info-value"><?php echo date('F Y', strtotime($user['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Profile Information</h5>
                        <form id="profileUpdateForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="firstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastName" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone_number" 
                                               value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company" class="form-label">Company Name</label>
                                        <input type="text" class="form-control" id="company" name="company_name" 
                                               value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="industry" class="form-label">Industry</label>
                                        <input type="text" class="form-control" id="industry" name="industry" 
                                               value="<?php echo htmlspecialchars($user['industry'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="description" class="form-label">Company Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($user['description'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    document.querySelector('.image-upload-overlay').addEventListener('click', function() {
        document.getElementById('profile_image').click();
    });

    document.getElementById('profileUpdateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success';
                alertDiv.textContent = 'Profile updated successfully!';
                this.insertAdjacentElement('beforebegin', alertDiv);
                
                setTimeout(() => {
                    alertDiv.remove();
                    location.reload();
                }, 2000);
            } else {
                throw new Error(data.message || 'Error updating profile');
            }
        })
        .catch(error => {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = error.message;
            this.insertAdjacentElement('beforebegin', alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        });
    });
    </script>
</body>
</html>