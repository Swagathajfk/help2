<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = in_array($_POST['role'], ['freelancer', 'client', 'admin']) ? $_POST['role'] : 'client';
    $username = substr($email, 0, strpos($email, '@'));
    
    $errors = [];
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    
    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    if (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    if (!preg_match("/[^a-zA-Z0-9]/", $password)) {
        $errors[] = "Password must contain at least one special character.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email is already registered.";
    }
    $stmt->close();
    
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
            
            // Insert into users table
            $stmt_user = $conn->prepare("INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("ssss", $email, $username, $hashed_password, $role);
            $stmt_user->execute();
            $user_id = $stmt_user->insert_id;
            $stmt_user->close();
            
            // Insert into role-specific tables
            if ($role === 'client' || $role === 'freelancer') {
                $table_name = $role === 'client' ? 'clients' : 'freelancers';
                $stmt_role = $conn->prepare("INSERT INTO $table_name (id, first_name, last_name, email, password) VALUES (?, '', '', ?, ?)");
                $stmt_role->bind_param("iss", $user_id, $email, $hashed_password);
                $stmt_role->execute();
                $stmt_role->close();
            }
            
            $conn->commit();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            
            // Redirect based on role
            $redirect_page = match($role) {
                'admin' => 'admin_dashboard.php',
                'freelancer' => 'freelancer_dashboard.php',
                'client' => 'client_dashboard.php',
                default => 'dashboard.php'
            };
            
            header("Location: $redirect_page");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed. Please try again. " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Professional Freelancing Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
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
        
        .registration-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            animation: fadeIn 0.5s ease-in-out;
            border: 1px solid rgba(212, 175, 55, 0.1);
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
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .signup-link {
            color: var(--gold);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .signup-link:hover {
            color: #c4a032;
        }
        
        h2 {
            color: var(--primary);
            font-weight: 800;
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
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <h2 class="text-center mb-4">Create Your Account</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" 
                       required 
                       placeholder="Enter your email">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       required 
                       placeholder="Create a strong password">
                <small class="form-text text-muted">
                    Password must be 8+ characters with uppercase, lowercase, number, and special character
                </small>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" 
                       class="form-control" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required 
                       placeholder="Repeat your password">
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label">Account Type</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="client">Client</option>
                    <option value="freelancer">Freelancer</option>
                   
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>
        
        <div class="text-center mt-3">
            <p class="mb-0">
                Already have an account? 
                <a href="login.php" class="signup-link">Log In</a>
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>