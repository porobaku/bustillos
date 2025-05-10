<?php
// Include database connection
include 'db.php';
session_start();

$paypal_business_email = "your-sandbox-email@example.com"; // Replace with your PayPal sandbox business email
$success_message = '';
$error_message = '';

// Process form submission and optionally save payment info in database (simulate before redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple validation
    $item_name = trim($_POST['item_name'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $currency_code = $_POST['currency_code'] ?? 'USD';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($item_name === '' || $amount <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please provide a valid item name, positive amount, and valid email.";
    } else {
        // Store data in your database - example insert into payments table (create this table as needed)
        $stmt = $conn->prepare("INSERT INTO paypal_payments (item_name, amount, currency_code, first_name, last_name, email, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sdssss", $item_name, $amount, $currency_code, $first_name, $last_name, $email);
        if ($stmt->execute()) {
            $success_message = "Ready to redirect to PayPal...";
            // Normally you might redirect here or auto-submit the PayPal form
            // For demo, we will show the form below with pre-filled data
        } else {
            $error_message = "Failed to save payment info: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PayPal Sandbox Payment Form</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f6f8fb;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .paypal-form {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 100%;
      text-align: center;
    }
    h1 {
      color: #003087;
      margin-bottom: 1rem;
    }
    label {
      display: block;
      text-align: left;
      margin: 0.5rem 0 0.2rem 0;
      font-weight: bold;
      color: #555;
    }
    input[type="text"],
    input[type="email"],
    input[type="number"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      box-sizing: border-box;
      font-size: 1rem;
      margin-bottom: 1rem;
    }
    input[type="submit"] {
      background-color: #003087;
      color: white;
      border: none;
      font-size: 1.1rem;
      font-weight: bold;
      padding: 12px;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 1.5rem;
      width: 100%;
      transition: background-color 0.3s ease;
    }
    input[type="submit"]:hover {
      background-color: #001f5b;
    }
    .message {
      margin-bottom: 1rem;
      padding: 12px;
      border-radius: 8px;
    }
    .success {
      background-color: #d4edda;
      color: #155724;
    }
    .error {
      background-color: #f8d7da;
      color: #721c24;
    }
  </style>
</head>
<body>
  <form class="paypal-form" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" target="_blank">
    <h1>Pay with PayPal Sandbox</h1>

    <?php if ($success_message): ?>
      <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php elseif ($error_message): ?>
      <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <input type="hidden" name="business" value="<?php echo htmlspecialchars($paypal_business_email); ?>" />
    <input type="hidden" name="cmd" value="_xclick" />

    <label for="item_name">Item Name</label>
    <input type="text" id="item_name" name="item_name" placeholder="Your product or service" value="<?php echo htmlspecialchars($item_name ?? '') ?>" required />

    <label for="amount">Amount (USD)</label>
    <input type="number" id="amount" name="amount" min="0.01" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($amount ?? '') ?>" required />

    <label for="currency_code">Currency</label>
    <input type="text" id="currency_code" name="currency_code" value="USD" readonly />

    <label for="first_name">First Name</label>
    <input type="text" id="first_name" name="first_name" placeholder="Your first name" value="<?php echo htmlspecialchars($first_name ?? '') ?>" />

    <label for="last_name">Last Name</label>
    <input type="text" id="last_name" name="last_name" placeholder="Your last name" value="<?php echo htmlspecialchars($last_name ?? '') ?>" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="your-email@example.com" value="<?php echo htmlspecialchars($email ?? '') ?>" required />

    <input type="submit" value="Pay Now" />
  </form>
</body>
</html>