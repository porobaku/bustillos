<?php
include 'db.php'; // Include your database connection
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Consider using password_hash for better security

    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if ($user['role'] == 'admin') {
            $error = "Admins must log in via the <a href='admin_login.php'>admin login page</a>.";
        } else {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_id'] = $user['id'];

            header("Location: upload_product.php");
            exit();
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, rgb(182, 168, 197), rgb(45, 78, 134));
            font-family: Arial, sans-serif;
        }

        .alert {
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container vh-100 d-flex justify-content-center align-items-center">
    <div class="row w-100">
        <!-- Left Side: Image -->
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <img src="img/img6.png" alt="Login Image" class="img-fluid" style="max-width: 100%; height: auto;">
        </div>

        <!-- Right Side: Login Form -->
        <div class="col-md-6 d-flex justify-content-center align-items-center">
            <div class="card p-4 shadow-lg w-100" style="max-width: 400px;">
                <h2 class="text-center">User Login</h2>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger text-center"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" required placeholder="Username">
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" required placeholder="Password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <p class="text-center mt-3"><a href="register.php">Don't have an account? Register here</a></p>
                <p class="text-center mt-2"><a href="admin_login.php">Admin Login</a></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
