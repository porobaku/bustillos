<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    header("Location: upload_product.php");
    exit();
}

$product_id = $_GET['id'];
$query = "SELECT * FROM products WHERE id = '$product_id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<h2 class='text-center text-danger'>Product not found!</h2>";
    exit();
}

$product = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, rgb(45, 31, 59), rgb(5, 53, 138));
            color: white;
        }
        .container {
            margin-top: 50px;
        }
        .card {
            border-radius: 10px;
            max-width: 800px;
            margin: auto;
        }
        .card img {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: contain;
            border-radius: 10px 10px 0 0;
        }
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .back-btn {
            display: block;
            margin: 20px auto;
            width: fit-content;
        }
    </style>
</head>
<body>

    <div class="container">
        <a href="upload_product.php" class="btn btn-light back-btn">Back to Products</a>
        <div class="card p-4 shadow-lg">
            <img src="<?php echo $product['image']; ?>" alt="Product Image">
            <div class="card-body text-center">
                <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <h4>Price: $<?php echo htmlspecialchars($product['price']); ?></h4>
                
                <div class="btn-container">
                    <?php if ($product['user_id'] == $_SESSION['user_id']) { ?>
                        <form method="post" action="delete_product.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    <?php } else { ?>
                        <form method="post" action="buy_product.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-success">Buy</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
