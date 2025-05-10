<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];  // Collecting the email
    $password = md5($_POST['password']); // Encrypting the password

    // Insert user data into the database
    $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    if (mysqli_query($conn, $query)) {
        echo "<div class='alert alert-success'>Registration successful. <a href='login.php'>Login</a></div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, rgb(98, 73, 124), rgb(35, 59, 102));
            font-family: Arial, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: white;
            border-radius: 10px;
        }
        .alert {
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4 shadow-lg">
            <h2 class="text-center">Register</h2>
            <form method="post">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" required placeholder="Username">
                </div>
                <div class="mb-3">
                    <input type="email" name="email" class="form-control" required placeholder="Email">
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" required placeholder="Password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <p class="text-center mt-3">Already have an account? <a href='login.php'>Login</a></p>
        </div>
    </div>
</body>
</html>
