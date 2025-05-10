<?php
include 'db.php';
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Initialize message variable for alerts
$message = '';
$messageClass = '';

// Get balance
$query = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$current_balance = $user['balance'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['currency'])) {
        $currency = (float) $_POST['currency'];
        if ($currency > 0) {
            $update_balance = "UPDATE users SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($update_balance);
            $stmt->bind_param("di", $currency, $user_id);
            if ($stmt->execute()) {
                $current_balance += $currency;
                $message = "Balance updated successfully!";
                $messageClass = "success";
            } else {
                $message = "Failed to update balance.";
                $messageClass = "danger";
            }
        } else {
            $message = "Please enter a valid amount to add.";
            $messageClass = "warning";
        }
    } elseif (isset($_POST['buy_product'])) {
        $product_id = (int) $_POST['product_id'];
        $product_query = "SELECT * FROM products WHERE id = ?";
        $stmt = $conn->prepare($product_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_result = $stmt->get_result();
        $product = $product_result->fetch_assoc();

        if ($product) {
            if ($current_balance >= $product['price']) {
                $new_balance = $current_balance - $product['price'];
                $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $stmt->bind_param("di", $new_balance, $user_id);
                if ($stmt->execute()) {
                    $stmt = $conn->prepare("INSERT INTO purchases (user_id, product_id, purchase_date) VALUES (?, ?, NOW())");
                    $stmt->bind_param("ii", $user_id, $product_id);
                    if ($stmt->execute()) {
                        $current_balance = $new_balance;
                        $message = "Purchase successful!";
                        $messageClass = "success";
                    } else {
                        $message = "Failed to record purchase.";
                        $messageClass = "danger";
                    }
                } else {
                    $message = "Failed to update balance.";
                    $messageClass = "danger";
                }
            } else {
                $check_pending = $conn->prepare("SELECT * FROM pending_purchases WHERE user_id = ? AND product_id = ?");
                $check_pending->bind_param("ii", $user_id, $product_id);
                $check_pending->execute();
                $pending_result = $check_pending->get_result();
                if ($pending_result->num_rows == 0) {
                    $stmt = $conn->prepare("INSERT INTO pending_purchases (user_id, product_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $user_id, $product_id);
                    if ($stmt->execute()) {
                        $message = "Insufficient funds. Purchase added to pending list.";
                        $messageClass = "warning";
                    } else {
                        $message = "Failed to add to pending purchases.";
                        $messageClass = "danger";
                    }
                } else {
                    $message = "This product is already in your pending list.";
                    $messageClass = "info";
                }
            }
        }
    } elseif (isset($_POST['complete_purchase'])) {
        $pending_id = (int) $_POST['pending_id'];
        $pending_query = "SELECT pp.id, pp.product_id, p.price 
            FROM pending_purchases pp 
            JOIN products p ON pp.product_id = p.id 
            WHERE pp.id = ? AND pp.user_id = ?";
        $stmt = $conn->prepare($pending_query);
        $stmt->bind_param("ii", $pending_id, $user_id);
        $stmt->execute();
        $pending_result = $stmt->get_result();
        $pending = $pending_result->fetch_assoc();

        if ($pending && $current_balance >= $pending['price']) {
            $new_balance = $current_balance - $pending['price'];
            
            $update_balance = "UPDATE users SET balance = ? WHERE id = ?";
            $stmt = $conn->prepare($update_balance);
            $stmt->bind_param("di", $new_balance, $user_id);
            if ($stmt->execute()) {
                $insert_purchase = "INSERT INTO purchases (user_id, product_id, purchase_date) VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($insert_purchase);
                $stmt->bind_param("ii", $user_id, $pending['product_id']);
                if ($stmt->execute()) {
                    $delete_pending = "DELETE FROM pending_purchases WHERE id = ?";
                    $stmt = $conn->prepare($delete_pending);
                    $stmt->bind_param("i", $pending_id);
                    if ($stmt->execute()) {
                        $current_balance = $new_balance;
                        $message = "Pending purchase completed successfully!";
                        $messageClass = "success";
                    } else {
                        $message = "Failed to delete pending purchase.";
                        $messageClass = "danger";
                    }
                } else {
                    $message = "Failed to record purchase.";
                    $messageClass = "danger";
                }
            } else {
                $message = "Failed to update balance.";
                $messageClass = "danger";
            }
        } else {
            $message = "Not enough balance to complete this purchase.";
            $messageClass = "danger";
        }
    } elseif (isset($_POST['delete_purchase'])) {
        $purchase_id = (int) $_POST['purchase_id'];
        $delete_query = "DELETE FROM purchases WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $purchase_id, $user_id);
        if ($stmt->execute()) {
            $message = "Purchase deleted successfully!";
            $messageClass = "success";
        } else {
            $message = "Failed to delete purchase.";
            $messageClass = "danger";
        }
    } elseif (isset($_POST['edit_product'])) {
        $product_id = (int) $_POST['product_id'];
        $product_name = trim($_POST['product_name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);

        if ($product_name != '' && $price >= 0) {
            $update_query = "UPDATE products SET product_name = ?, description = ?, price = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssdii", $product_name, $description, $price, $product_id, $user_id);
            if ($stmt->execute()) {
                $message = "Product updated successfully!";
                $messageClass = "success";
            } else {
                $message = "Failed to update product.";
                $messageClass = "danger";
            }
        } else {
            $message = "Please provide a valid product name and price.";
            $messageClass = "warning";
        }
    }
}

// Fetch Data
$purchases_result = $conn->prepare("SELECT p.id as pid, p.product_name, p.description, p.price, pu.purchase_date, pu.id 
    FROM purchases pu JOIN products p ON pu.product_id = p.id WHERE pu.user_id = ?");
$purchases_result->bind_param("i", $user_id);
$purchases_result->execute();
$purchases = $purchases_result->get_result();

$uploads_result = $conn->prepare("SELECT * FROM products WHERE user_id = ?");
$uploads_result->bind_param("i", $user_id);
$uploads_result->execute();
$uploads = $uploads_result->get_result();

$pending_result = $conn->prepare("SELECT pp.id as pp_id, p.product_name, p.description, p.price 
    FROM pending_purchases pp JOIN products p ON pp.product_id = p.id WHERE pp.user_id = ?");
$pending_result->bind_param("i", $user_id);
$pending_result->execute();
$pending = $pending_result->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>EN EP T - Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
      rel="stylesheet"
    />
    <style>
      body {
        background: #0F1113;
        color: #E4E6EB;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
      nav.navbar {
        background: #161B22;
        box-shadow: 0 0 12px #050505cc;
        z-index: 1051;
      }
      nav .navbar-brand {
        font-weight: 700;
        font-size: 1.4rem;
        color: #55A0FF;
        user-select: none;
      }
      nav .navbar-brand img {
        height: 36px;
        margin-right: 10px;
        filter: drop-shadow(0 0 2px #55A0FF);
      }
      nav .navbar-nav .nav-link {
        color: #A6A8AA;
        font-weight: 500;
        padding: 8px 12px;
        transition: color 0.3s;
      }
      nav .navbar-nav .nav-link:hover {
        color: #55A0FF;
      }
      nav .navbar-nav .nav-link.active {
        color: #55A0FF;
        font-weight: 700;
      }
      /* Profile header */
      .profile-header {
        background: #16191F;
        padding: 2rem 1rem 3rem;
        box-shadow: 0 6px 10px rgba(0, 0, 0, 0.7);
        text-align: center;
        position: relative;
        border-radius: 0 0 20px 20px;
        margin-bottom: 2rem;
      }
      .profile-avatar {
        margin-top: -70px;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: #222933;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 3px 15px #55A0FFaa;
        border: 4px solid #16191F;
      }
      .profile-avatar img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
        display: block;
      }
      .profile-name {
        margin: 1rem 0 0.3rem;
        font-weight: 700;
        font-size: 1.8rem;
        color: #55A0FF;
        user-select: none;
      }
      .profile-balance {
        font-weight: 600;
        font-size: 1.2rem;
        color: #B0B5BA;
        margin-bottom: 0.3rem;
      }
      /* Tabs */
      .nav-tabs .nav-link {
        color: #999DA1;
        font-weight: 600;
        border: none;
        border-radius: 8px 8px 0 0;
        background: #16191F;
        margin-right: 4px;
        transition: background-color 0.3s ease;
      }
      .nav-tabs .nav-link.active,
      .nav-tabs .nav-link:hover {
        color: #55A0FF;
        background: #242933;
        box-shadow: 0 6px 12px #55A0FF50;
      }
      /* Tab content cards grid */
      .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        padding: 1rem 0;
      }
      .nft-card {
        background: #16191F;
        border-radius: 15px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.6);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: default;
        color: #E4E6EB;
      }
      .nft-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 25px rgba(85, 160, 255, 0.6);
      }
      .nft-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-bottom: 1px solid #242933;
        background: #222931;
        filter: drop-shadow(0 0 1px rgba(85, 160, 255, 0.7));
        transition: transform 0.3s;
      }
      .nft-card:hover img {
        transform: scale(1.05);
      }
      .nft-card .card-body {
        padding: 15px 18px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .nft-card .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #55A0FF;
      }
      .nft-card .card-text {
        font-size: 0.9rem;
        color: #B0B5BA;
        flex-grow: 1;
        margin-bottom: 10px;
        overflow: hidden;
        max-height: 3.6em;
        line-height: 1.2em;
      }
      .nft-card .price {
        font-weight: 600;
        font-size: 1.1rem;
        color: #FFFFFF;
        margin-bottom: 10px;
        text-align: left;
      }
      .nft-card .btn-container {
        display: flex;
        gap: 8px;
      }
      .nft-card .btn {
        flex-grow: 1;
        border-radius: 30px;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 8px 0;
        transition: all 0.3s ease;
        user-select: none;
      }
      .btn-buy {
        background: #3BB54A;
        color: white;
        border: none;
        box-shadow: 0 3px 10px #3BB54Aaa;
      }
      .btn-buy:hover {
        background: #2B9348;
        box-shadow: 0 4px 16px #2B9348cc;
      }
      .btn-delete {
        background: #D9534F;
        color: white;
        border: none;
        box-shadow: 0 3px 10px #D9534Faa;
      }
      .btn-delete:hover {
        background: #B9403E;
        box-shadow: 0 4px 16px #B9403Ecc;
      }
      /* Responsive */
      @media (max-width: 576px) {
        .cards-grid {
          grid-template-columns: 1fr;
        }
        .profile-avatar {
          width: 100px;
          height: 100px;
          margin-top: -50px;
        }
        .nft-card img {
          height: 150px;
        }
      }
      /* Form styles */
      .form-control,
      .form-select {
        background: #222933;
        border: 1px solid #444c57;
        color: #E4E6EB;
      }
      .form-control:focus,
      .form-select:focus {
        background: #2f3450;
        border-color: #55A0FF;
        color: #E4E6EB;
        box-shadow: 0 0 6px #55A0FFcc;
      }
      .modal-content {
        background: #16191F;
        color: #E4E6EB;
      }
      .btn-close {
        filter: invert(1);
      }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid">
      
      <a class="navbar-brand d-flex align-items-center" href="upload_product.php">
          <img src="img/img7.png" alt="EN EP T Logo" style="height:36px; margin-right:10px; filter: drop-shadow(0 0 2px #55A0FF);" />
          EN EP T
      </a>

      <ul class="navbar-nav ms-auto flex-row gap-3">
        <li class="nav-item">
            <a href="logout.php" class="btn btn-outline-danger px-3 py-1">Logout <i class="bi bi-box-arrow-right"></i></a>
        </li>
      </ul>
  </div>
</nav>

<div class="container my-4">
    <?php if($message): ?>
    <div class="alert alert-<?php echo htmlspecialchars($messageClass); ?> text-center" role="alert">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <header class="profile-header">
        <div class="profile-avatar">
            <!-- Placeholder avatar, you can add actual user avatar -->
            <i class="bi bi-person-circle" style="font-size: 100px; color: #55A0FF;"></i>
        </div>
        <h1 class="profile-name"><?php echo htmlspecialchars($username); ?></h1>
        <p class="profile-balance">Balance: $<?php echo number_format($current_balance, 2); ?></p>
        <form method="post" class="d-flex justify-content-center gap-2" style="max-width:320px; margin: auto;">
            <input type="number" step="0.01" min="1" name="currency" class="form-control" placeholder="Add funds" required />
            <button type="submit" class="btn btn-primary">Add</button>
        </form>
    </header>

    <ul class="nav nav-tabs" id="profileTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="collected-tab" data-bs-toggle="tab" data-bs-target="#collected" type="button" role="tab" aria-controls="collected" aria-selected="true">Collected</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="false">Pending</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="created-tab" data-bs-toggle="tab" data-bs-target="#created" type="button" role="tab" aria-controls="created" aria-selected="false">Created</button>
      </li>
    </ul>

    <div class="tab-content" id="profileTabsContent">
      <!-- Collected -->
      <div class="tab-pane fade show active" id="collected" role="tabpanel" aria-labelledby="collected-tab" tabindex="0">
        <div class="cards-grid mt-3">
          <?php if ($purchases->num_rows === 0): ?>
            <p class="text-center text-muted">No NFTs purchased yet.</p>
          <?php else: ?>
            <?php while ($purchase = $purchases->fetch_assoc()): ?>
              <article class="nft-card" tabindex="0" aria-label="<?php echo htmlspecialchars($purchase['product_name']); ?>, $<?php echo number_format($purchase['price'],2); ?>">
                <img src="<?php echo htmlspecialchars($purchase['image'] ?? 'img/img7.png'); ?>" alt="<?php echo htmlspecialchars($purchase['product_name']); ?>" loading="lazy" />
                <div class="card-body">
                  <h3 class="card-title" title="<?php echo htmlspecialchars($purchase['product_name']); ?>">
                    <?php echo htmlspecialchars($purchase['product_name']); ?>
                  </h3>
                  <p class="card-text"><?php echo htmlspecialchars($purchase['description']); ?></p>
                  <p class="price">$<?php echo number_format($purchase['price'], 2); ?></p>
                  <small class="text-muted">Purchased at: <?php echo date("Y-m-d", strtotime($purchase['purchase_date'])); ?></small>
                  <form method="post" class="btn-container mt-2" onsubmit="return confirm('Delete this purchase?');">
                    <input type="hidden" name="purchase_id" value="<?php echo $purchase['id']; ?>" />
                    <button type="submit" name="delete_purchase" class="btn btn-delete" aria-label="Delete purchase of <?php echo htmlspecialchars($purchase['product_name']); ?>">Delete</button>
                  </form>
                </div>
              </article>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Pending -->
      <div class="tab-pane fade" id="pending" role="tabpanel" aria-labelledby="pending-tab" tabindex="0">
        <div class="cards-grid mt-3">
          <?php if ($pending->num_rows === 0): ?>
            <p class="text-center text-muted">No pending purchases.</p>
          <?php else: ?>
            <?php while ($item = $pending->fetch_assoc()): ?>
              <article class="nft-card" tabindex="0" aria-label="<?php echo htmlspecialchars($item['product_name']); ?>, $<?php echo number_format($item['price'], 2); ?>">
                <img src="<?php echo htmlspecialchars($item['image'] ?? 'img/img7.png'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" loading="lazy" />
                <div class="card-body">
                  <h3 class="card-title" title="<?php echo htmlspecialchars($item['product_name']); ?>">
                    <?php echo htmlspecialchars($item['product_name']); ?>
                  </h3>
                  <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                  <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                  <form method="post" class="btn-container">
                    <input type="hidden" name="pending_id" value="<?php echo $item['pp_id']; ?>">
                    <button type="submit" name="complete_purchase" class="btn btn-buy">Complete</button>
                  </form>
                </div>
              </article>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>


      <!-- Created -->
      <div class="tab-pane fade" id="created" role="tabpanel" aria-labelledby="created-tab" tabindex="0">
        <div class="cards-grid mt-3">
          <?php if ($uploads->num_rows === 0): ?>
            <p class="text-center text-muted">No products uploaded yet.</p>
          <?php else: ?>
            <?php while ($product = $uploads->fetch_assoc()): ?>
              <article class="nft-card" tabindex="0" aria-label="Your product <?php echo htmlspecialchars($product['product_name']); ?>">
                <img src="<?php echo htmlspecialchars($product['image'] ?? 'img/img7.png'); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" loading="lazy" />
                <div class="card-body d-flex flex-column justify-content-between">
                  <h3 class="card-title" title="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <?php echo htmlspecialchars($product['product_name']); ?>
                  </h3>
                  <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                  <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                  <button type="button" class="btn btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                      Edit
                  </button>

                  <!-- Edit Product Modal -->
                  <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1" aria-labelledby="editProductModalLabel<?php echo $product['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="editProductModalLabel<?php echo $product['id']; ?>">Edit Product</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post" action="">
                          <div class="modal-body">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>" />
                            <div class="mb-3">
                              <label for="product_name_<?php echo $product['id']; ?>" class="form-label">Product Name</label>
                              <input type="text" class="form-control" id="product_name_<?php echo $product['id']; ?>" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required />
                            </div>
                            <div class="mb-3">
                              <label for="description_<?php echo $product['id']; ?>" class="form-label">Description</label>
                              <textarea class="form-control" id="description_<?php echo $product['id']; ?>" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>
                            <div class="mb-3">
                              <label for="price_<?php echo $product['id']; ?>" class="form-label">Price</label>
                              <input type="number" step="0.01" min="0" class="form-control" id="price_<?php echo $product['id']; ?>" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required />
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit_product" class="btn btn-primary">Save Changes</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>

                </div>
              </article>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
</content>
</create_file>
