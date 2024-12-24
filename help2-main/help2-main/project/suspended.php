<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5 text-center">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h3 class="card-title text-danger mb-4">Account Suspended</h3>
                <p class="card-text">Your account has been suspended. Please contact administration for more information.</p>
                <p class="card-text">Email: admin@support.com</p>
                <a href="logout.php" class="btn btn-primary mt-3">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>