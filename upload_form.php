<?php
include 'db.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container mt-5">
  <h2 class="text-center mb-4">Upload a New Product</h2>
  <form method="POST" enctype="multipart/form-data" action="upload_product.php" class="bg-secondary p-4 rounded">
    <div class="mb-3">
      <label for="product_name" class="form-label">Product Name</label>
      <input type="text" class="form-control" name="product_name" required>
    </div>
    <div class="mb-3">
      <label for="description" class="form-label">Description</label>
      <textarea class="form-control" name="description" required></textarea>
    </div>
    <div class="mb-3">
      <label for="price" class="form-label">Price (USD)</label>
      <input type="number" class="form-control" name="price" required>
    </div>
    <div class="mb-3">
      <label for="image" class="form-label">Image (JPG, PNG)</label>
      <input type="file" class="form-control" name="image" accept="image/jpeg, image/png" required>
    </div>
    <button type="submit" class="btn btn-light">Upload</button>
    <a href="upload_product.php" class="btn btn-outline-light ms-2">Back</a>
  </form>
</div>
</body>
</html>
