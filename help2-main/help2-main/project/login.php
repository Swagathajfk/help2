<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error_message = "Please fill in both email and password.";
    } else {
        // Modified SQL to include all needed user data
        $sql = "SELECT id, email, password, role, status FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] === 'suspended') {
                    $_SESSION['suspended_message'] = "Your account has been suspended.";
                    header("Location: suspended.php");
                    exit;
                }

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Redirect based on role
                switch($user['role']) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;
                    case 'freelancer':
                        header("Location: freelancer_dashboard.php");
                        break;
                    case 'client':
                        header("Location: client_dashboard.php");
                        break;
                    default:
                        header("Location: login.php");
                }
                exit();
            } else {
                $error_message = "Incorrect password.";
            }
        } else {
            $error_message = "No account found with this email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Kat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary: #0a0a0a;
            --secondary: #1a1a1a;
            --accent: #2a2a2a;
            --gold: #d4af37;
            --silver: #c0c0c0;
            --text: #333;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            color: var(--text);
        }

        .container {
            width: 100%;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(212, 175, 55, 0.1);
            color: var(--primary);
            font-weight: 700;
            font-size: 1.25rem;
            padding: 1.5rem;
        }

        .form-control {
            border-radius: 6px;
            padding: 0.75rem;
            border: 1px solid #e1e1e1;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.1);
        }

        .btn-primary {
            background: var(--gold);
            border: none;
            color: var(--primary);
            transition: all 0.3s ease;
            padding: 0.8rem 2rem;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #c4a032;
            transform: translateY(-2px);
            color: var(--primary);
        }

        .card-footer {
            background: white;
            border-top: 1px solid rgba(212, 175, 55, 0.1);
            padding: 1rem;
        }

        .card-footer a {
            color: var(--gold);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .card-footer a:hover {
            color: #c4a032;
        }

        .form-label {
            font-weight: 500;
            color: var(--text);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card border-0">
                <div class="card-header">Log In to Your Account 🔐</div>
                <div class="card-body p-4">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" id="email" placeholder="Enter email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" id="password" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Log In</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    Don't have an account? <a href="register.php">Sign Up</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>