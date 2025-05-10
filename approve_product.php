<?php
include 'db.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_POST['approve'])) {
    $product_id = $_POST['product_id'];
    $query = "UPDATE products SET status = 'approved' WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin.php?approved=1");
    exit();
}
?>
