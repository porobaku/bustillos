<?php
include 'db.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Delete related purchases first to satisfy foreign key constraint
    $deletePurchasesQuery = "DELETE FROM purchases WHERE product_id = '$delete_id'";
    mysqli_query($conn, $deletePurchasesQuery);

    // Now delete the product
    $query = "DELETE FROM products WHERE id = '$delete_id'";
    if (mysqli_query($conn, $query)) {
        $success_message = "Product and related purchases deleted successfully";
    } else {
        $error_message = "Error deleting product";
    }
}

// Handle product editing
if (isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $query = "UPDATE products SET product_name = '$product_name', description = '$description', price = '$price' WHERE id = '$edit_id'";
    if (mysqli_query($conn, $query)) {
        $success_message = "Product updated successfully";
    } else {
        $error_message = "Error updating product";
    }
}

// Fetch all products
$query = "SELECT * FROM products";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(135deg, #f0f0f0, #d0d0d0);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: black;
            min-height: 100vh;
            margin: 0;
            padding-top: 80px;
        }
        .container {
            max-width: 900px;
            background: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow:
                0 4px 8px rgba(0, 0, 0, 0.1),
                0 0 15px 5px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease-in-out;
        }
        .container:hover {
            box-shadow:
                0 8px 16px rgba(0, 0, 0, 0.15),
                0 0 25px 10px rgba(0, 0, 0, 0.08);
        }
        nav.navbar {
            background-color: #ffffff;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1050;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: background-color 0.3s ease;
        }
        nav.navbar:hover {
            background-color: #f8f9fa;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
            transition: transform 0.3s ease;
        }
        .navbar-brand img:hover {
            transform: scale(1.1) rotate(-5deg);
        }
        .btn-primary {
            background: #2c7be5;
            border: none;
            box-shadow: 0 4px 10px #4a90e2;
            transition: background 0.3s ease, box-shadow 0.3s ease;
            color: white;
        }
        .btn-primary:hover {
            background: #1a5bc7;
            box-shadow: 0 6px 15px #1a5bc7cc;
        }
        .table {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .table-hover tbody tr:hover {
            background-color: #e7f1ff;
            cursor: pointer;
            transform: scale(1.02);
            box-shadow: 0 2px 15px rgba(44, 123, 229, 0.3);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            color: black;
        }
        .table th, .table td {
            vertical-align: middle;
            color: black;
            border-color: #dee2e6;
        }
        .btn-warning {
            background: #f0ad4e;
            border: none;
            box-shadow: 0 3px 10px #f0ad4ea0;
            transition: background 0.3s ease, box-shadow 0.3s ease;
            color: black;
        }
        .btn-warning:hover {
            background: #ec971f;
            box-shadow: 0 4px 15px #c98f1be0;
            color: black;
        }
        .btn-danger {
            background: #d9534f;
            border: none;
            box-shadow: 0 3px 10px #d9534fa0;
            transition: background 0.3s ease, box-shadow 0.3s ease;
            color: white;
        }
        .btn-danger:hover {
            background: #b52b27;
            box-shadow: 0 4px 15px #9e2420e0;
            color: white;
        }
        /* Scrollbar styling for modern browsers */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(44, 123, 229, 0.4);
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(44, 123, 229, 0.7);
        }
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="img/img7.png" alt="Logo" />
                EN EP T
            </a>
            <div class="d-flex">
                <a href="upload_product.php" class="btn btn-primary">Go to User Panel</a>
            </div>
        </div>
    </nav>

    <div class="container shadow-lg">
        <h2 class="text-center mb-4" style="color: #2c7be5;">Admin Panel</h2>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Price ($)</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['description']); ?></td>
                            <td><?php echo htmlspecialchars(number_format((float)$product['price'], 2)); ?></td>
                            <td class="text-center">
                                <!-- Edit Button triggers modal -->
                                <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $product['id']; ?>">
                                    Edit
                                </button>
                                <a href="admin.php?delete_id=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');">
                                    Delete
                                </a>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?php echo $product['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $product['id']; ?>" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <div class="modal-content bg-white text-black">
                                      <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?php echo $product['id']; ?>">Edit Product</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                      </div>
                                      <form method="post" action="admin.php">
                                        <div class="modal-body">
                                            <input type="hidden" name="edit_id" value="<?php echo $product['id']; ?>" />
                                            <div class="mb-3">
                                                <label for="productName<?php echo $product['id']; ?>" class="form-label">Product Name</label>
                                                <input type="text" class="form-control" id="productName<?php echo $product['id']; ?>" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required />
                                            </div>
                                            <div class="mb-3">
                                                <label for="description<?php echo $product['id']; ?>" class="form-label">Description</label>
                                                <textarea class="form-control" id="description<?php echo $product['id']; ?>" name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="price<?php echo $product['id']; ?>" class="form-label">Price ($)</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="price<?php echo $product['id']; ?>" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required />
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Save Changes</button>
                                        </div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
</content>
</create_file>