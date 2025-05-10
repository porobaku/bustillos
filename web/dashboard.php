<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

echo "Welcome, " . $_SESSION['username'];
if ($_SESSION['role'] == 'admin') {
    echo "<br><a href='admin.php'>Go to Admin Panel</a>";
}
echo "<br><a href='logout.php'>Logout</a>";
?>

-- logout.php
<?php
session_start();
session_destroy();
header("Location: login.php");
?>
