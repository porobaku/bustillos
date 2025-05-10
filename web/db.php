<?php
$host = "localhost";
$user = "root";
$password = ""; // Set your MySQL password if you have one
$database = "user_system";

$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
