<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    $_SESSION['buy_error'] = "You must be logged in as a user to make a purchase.";
    header("Location: upload_product.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int) $_POST['product_id'];

    // Fetch product price
    $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();

    if ($product_result->num_rows === 0) {
        $_SESSION['buy_error'] = "Product not found.";
        header("Location: upload_product.php");
        exit();
    }

    $product = $product_result->fetch_assoc();
    $price = (float)$product['price'];

    // Fetch user balance
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows === 0) {
        $_SESSION['buy_error'] = "User account not found.";
        header("Location: upload_product.php");
        exit();
    }

    $user = $user_result->fetch_assoc();
    $balance = (float)$user['balance'];

    if ($balance >= $price) {
        // Deduct price from user balance
        $new_balance = $balance - $price;
        $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->bind_param("di", $new_balance, $user_id);

        if (!$stmt->execute()) {
            $_SESSION['buy_error'] = "Failed to update balance. Please try again.";
            header("Location: upload_product.php");
            exit();
        }

        // Record purchase
        $stmt = $conn->prepare("INSERT INTO purchases (user_id, product_id, purchase_date) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $user_id, $product_id);

        if (!$stmt->execute()) {
            $_SESSION['buy_error'] = "Failed to record purchase. Please try again.";
            header("Location: upload_product.php");
            exit();
        }

        $_SESSION['buy_success'] = "Purchase successful!";
        header("Location: upload_product.php");
        exit();
    } else {
        // Add to pending purchases if not already present
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM pending_purchases WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        $row = $check_result->fetch_assoc();

        if ($row['cnt'] == 0) {
            $stmt = $conn->prepare("INSERT INTO pending_purchases (user_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $product_id);
            if ($stmt->execute()) {
                $_SESSION['buy_warning'] = "Insufficient funds. Purchase added to pending list.";
            } else {
                $_SESSION['buy_error'] = "Failed to add to pending purchases. Please try again.";
            }
        } else {
            $_SESSION['buy_warning'] = "This product is already in your pending purchases list.";
        }
        header("Location: upload_product.php");
        exit();
    }
} else {
    $_SESSION['buy_error'] = "Invalid purchase attempt.";
    header("Location: upload_product.php");
    exit();
}
?></content>
</create_file>