<?php
include 'db.php';
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    // Sanitize input
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);

    // Validate inputs (simple)
    if (empty($product_name) || empty($price)) {
        $error_msg = "Product name and price are required.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error_msg = "Price must be a positive number.";
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Error uploading file. Error code: " . $_FILES['image']['error'];
    } else {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $image_file_type = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB max

        if (!in_array($image_file_type, $allowed_types)) {
            $error_msg = "Invalid image type. Only JPG, JPEG, PNG, and GIF are allowed.";
        } elseif ($_FILES['image']['size'] > $max_file_size) {
            $error_msg = "File size exceeds 5MB limit.";
        }

        if (!isset($error_msg)) {
            // Generate unique image name and save
            $new_image_name = uniqid('nft_') . '.' . $image_file_type;
            $image_path = $upload_dir . $new_image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                // Use prepared statement to insert product
                $stmt = $conn->prepare("INSERT INTO products (user_id, product_name, description, price, image) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issds", $user_id, $product_name, $description, $price, $image_path);

                if ($stmt->execute()) {
                    header("Location: upload_product.php");
                    exit();
                } else {
                    $error_msg = "Database error: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $error_msg = "Failed to move uploaded file.";
            }
        }
    }
}

$query = "SELECT * FROM products ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>EN EP T - NFT Marketplace</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    /* Reset and base */
    body {
      margin: 0;
      background: #0F1113; /* Dark background */
      color: #E4E6EB;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Navbar */
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
    nav .sidebar-toggle {
      font-size: 1.7rem;
      cursor: pointer;
      color: #55A0FF;
      user-select: none;
      border: none;
      background: transparent;
      padding: 4px 10px;
    }
    .navbar-nav .nav-link {
      color: #A6A8AA;
      font-weight: 500;
      padding: 8px 12px;
      transition: color 0.3s;
    }
    .navbar-nav .nav-link:hover {
      color: #55A0FF;
    }
    .navbar-nav .nav-link.active {
      color: #55A0FF;
      font-weight: 700;
    }

    /* Sidebar */
    #sidebar {
      height: 100vh;
      width: 0;
      position: fixed;
      top: 0;
      left: 0;
      background: #121518;
      overflow-x: hidden;
      overflow-y: auto;
      padding-top: 70px;
      transition: width 0.3s ease;
      border-right: 1px solid #22262B;
      z-index: 1049;
      flex-direction: column;
      display: flex;
    }

    #sidebar.open {
      width: 220px;
      box-shadow: 4px 0 25px rgba(20,30,40,.75);
    }

    #sidebar a {
      padding: 15px 20px;
      display: flex;
      align-items: center;
      color: #999DA1;
      text-decoration: none;
      font-weight: 600;
      font-size: 1rem;
      border-left: 4px solid transparent;
      transition: all 0.3s;
    }

    #sidebar a:hover,
    #sidebar a.active {
      color: #55A0FF;
      border-left: 4px solid #55A0FF;
      background: rgba(85,160,255,0.1);
    }

    #sidebar a i {
      margin-right: 12px;
      font-size: 1.3rem;
      width: 22px;
      text-align: center;
      filter: drop-shadow(0 0 1.5px #55A0FF);
    }

    /* Main content */
    #main-content {
      margin-left: 0;
      transition: margin-left 0.3s ease;
      padding: 80px 22px 40px 22px;
      flex-grow: 1;
      max-width: 100%;
    }
    #main-content.sidebar-opened {
      margin-left: 220px;
    }
    /* Search bar */
    .search-container {
      max-width: 600px;
      margin: 0 auto 24px auto;
      position: relative;
    }
    .search-container input {
      width: 100%;
      border-radius: 50px;
      padding-left: 50px;
      height: 44px;
      border: none;
      font-size: 1rem;
      background: #20242A;
      color: #E4E6EB;
      box-shadow: inset 0 0 6px #202835;
      transition: background 0.3s, box-shadow 0.3s;
    }
    .search-container input::placeholder {
      color: #777B82;
    }
    .search-container i {
      position: absolute;
      top: 50%;
      left: 18px;
      transform: translateY(-50%);
      font-size: 1.3rem;
      color: #55A0FF;
      pointer-events: none;
      user-select: none;
    }
    .search-container input:focus {
      background: #272B33;
      box-shadow: inset 0 0 8px #55A0FF;
      outline: none;
    }

    /* Cards grid */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill,minmax(260px,1fr));
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    /* Card */
    .nft-card {
      background: #16191F;
      border-radius: 15px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.6);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
      color: #E4E6EB;
    }
    .nft-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 10px 25px rgba(85,160,255,0.6);
    }

    .nft-card img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      border-bottom: 1px solid #242933;
      background: #222931;
      filter: drop-shadow(0 0 1px rgba(85,160,255,0.7));
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

    /* Responsive adjustments */
    @media (max-width: 576px) {
      #sidebar.open {
        width: 180px;
      }
      #main-content.sidebar-opened {
        margin-left: 180px;
      }
      .nft-card img {
        height: 180px;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <div id="sidebar" tabindex="-1" aria-label="Sidebar navigation">
    <a href="upload_product.php" class="active" aria-current="page"><i class="bi bi-house-door"></i> Dashboard</a>
    <!-- Updated My NFTs link -->
    <a href="profile.php"><i class="bi bi-collection"></i> My NFTs</a>
    <a href="upload_product.php"><i class="bi bi-upload"></i> Upload</a>
    <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>

  <!-- Navbar -->
  <nav class="navbar sticky-top navbar-expand-lg">
    <div class="container-fluid">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">☰</button>
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="img/img7.png" alt="EN EP T Logo" />
        EN EP T
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
              aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon" style="filter: invert(1)"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main id="main-content" role="main">
    <div class="search-container mb-4" role="search" aria-label="Search NFTs">
      <i class="bi bi-search"></i>
      <input type="search" id="search" placeholder="Search EN EP T..." aria-describedby="searchHint" autocomplete="off" />
      <small id="searchHint" class="text-muted d-block mt-1">Search by NFT name or description</small>
    </div>

    <div class="container-fluid px-0">
      <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger text-center" role="alert">
          <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <h2 class="mb-4 text-center fw-bold" style="color:#55A0FF">NFT Collectibles</h2>

      <div class="cards-grid" id="productsGrid" aria-live="polite">
        <?php while ($product = mysqli_fetch_assoc($result)): ?>
          <article class="nft-card" tabindex="0" aria-label="<?php echo htmlspecialchars($product['product_name']); ?>, $<?php echo htmlspecialchars($product['price']); ?>">
            <a href="product_details.php?id=<?php echo $product['id']; ?>" tabindex="-1">
              <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" loading="lazy" />
            </a>
            <div class="card-body">
              <h3 class="card-title" title="<?php echo htmlspecialchars($product['product_name']); ?>">
                <?php echo htmlspecialchars($product['product_name']); ?>
              </h3>
              <p class="card-text" title="<?php echo htmlspecialchars($product['description']); ?>">
                <?php
                  $desc = strip_tags($product['description']);
                  echo (strlen($desc) > 90) ? htmlspecialchars(substr($desc, 0, 87)) . '...' : htmlspecialchars($desc);
                ?>
              </p>
              <p class="price">$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>
              <div class="btn-container">
                <?php if ($product['user_id'] == $user_id): ?>
                  <form method="post" action="delete_product.php" onsubmit="return confirm('Are you sure you want to delete this product?');">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>" />
                    <button type="submit" class="btn btn-delete" aria-label="Delete <?php echo htmlspecialchars($product['product_name']); ?>">Delete</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="buy_product.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>" />
                    <button type="submit" class="btn btn-buy" aria-label="Buy <?php echo htmlspecialchars($product['product_name']); ?>">Buy</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.getElementById('main-content');

    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      mainContent.classList.toggle('sidebar-opened');
    });

    const searchInput = document.getElementById('search');
    searchInput.addEventListener('input', () => {
      const filter = searchInput.value.toLowerCase();
      const cards = document.querySelectorAll('.nft-card');
      cards.forEach(card => {
        const title = card.querySelector('.card-title').textContent.toLowerCase();
        const desc = card.querySelector('.card-text').textContent.toLowerCase();
        card.style.display = title.includes(filter) || desc.includes(filter) ? 'flex' : 'none';
      });
    });
  </script>
</body>
</html>