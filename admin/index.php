<?php
session_start();
require_once '../config/database.php';

require_once '../config/settings.php';

if (isset($_POST['login'])) {
    $_SESSION['admin_tries'] = $_SESSION['admin_tries'] ?? 0;
    if ($_SESSION['admin_tries'] >= 8) {
        $error = 'Too many attempts. Close the browser and try again later.';
    } elseif (hash_equals(ADMIN_PASSWORD, (string)($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_tries'] = 0;
    } else {
        $_SESSION['admin_tries']++;
        $error = 'Wrong password';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <title>Admin Login</title>
      <style>
        body { font-family: sans-serif; display:flex; align-items:center; justify-content:center; height:100vh; background:#f5f5f5; margin:0; }
        .login-box { background:white; padding:40px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.1); width:320px; }
        input { width:100%; padding:12px; margin:10px 0; border:1px solid #ddd; box-sizing:border-box; }
        button { width:100%; padding:12px; background:#0f0f0f; color:white; border:none; cursor:pointer; }
        .error { color:#c0392b; font-size:14px; }
      </style>
    </head>
    <body>
      <div class="login-box">
        <h2>Admin Login</h2>
        <?php if (!empty($error)): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST">
          <input type="password" name="password" placeholder="Password" required>
          <button type="submit" name="login">Login</button>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$revenue        = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$recent_orders  = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard - Mans Fashion</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Segoe UI', sans-serif; background:#f5f5f5; color:#333; }
    .sidebar { position:fixed; left:0; top:0; bottom:0; width:220px; background:#0f0f0f; color:white; padding:30px 20px; }
    .sidebar h2 { font-size:18px; margin-bottom:30px; letter-spacing:1px; }
    .sidebar a { display:block; color:#ccc; padding:10px 0; text-decoration:none; font-size:14px; }
    .sidebar a:hover { color:#c9a227; }
    .main { margin-left:220px; padding:30px; }
    .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:40px; }
    .stat-card { background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
    .stat-card h3 { font-size:13px; color:#888; text-transform:uppercase; margin-bottom:8px; }
    .stat-card p { font-size:28px; font-weight:600; }
    table { width:100%; background:white; border-collapse:collapse; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
    th, td { padding:14px 16px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th { background:#fafafa; font-weight:600; text-transform:uppercase; font-size:12px; letter-spacing:0.5px; }
    .badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:500; }
    .badge.pending { background:#fff3cd; color:#856404; }
    .badge.confirmed { background:#d4edda; color:#155724; }
    .badge.processing { background:#e2e3e5; color:#383d41; }
    .badge.shipped { background:#cce5ff; color:#004085; }
    .badge.delivered { background:#d1e7dd; color:#0f5132; }
    .badge.cancelled { background:#f8d7da; color:#721c24; }
    a.btn { display:inline-block; padding:6px 14px; background:#0f0f0f; color:white; text-decoration:none; font-size:13px; border-radius:4px; }
  </style>
</head>
<body>
  <div class="sidebar">
    <h2>MANS FASHION</h2>
    <a href="index.php">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="orders.php">Orders</a>
    <a href="reviews.php">Reviews</a>
    <a href="assistant.php">Chat assistant</a>
    <a href="?logout=1" style="margin-top:40px;color:#e74c3c;">Logout</a>
  </div>

  <div class="main">
    <h1 style="margin-bottom:30px;">Dashboard</h1>

    <div class="stats">
      <div class="stat-card">
        <h3>Total Products</h3>
        <p><?= $total_products ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Orders</h3>
        <p><?= $total_orders ?></p>
      </div>
      <div class="stat-card">
        <h3>Pending Orders</h3>
        <p><?= $pending_orders ?></p>
      </div>
      <div class="stat-card">
        <h3>Revenue</h3>
        <p>৳<?= number_format($revenue, 0) ?></p>
      </div>
    </div>

    <h2 style="margin-bottom:16px;font-size:18px;">Recent Orders</h2>
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recent_orders)): ?>
          <tr><td colspan="7" style="text-align:center;padding:30px;color:#888;">No orders yet</td></tr>
        <?php else: ?>
          <?php foreach ($recent_orders as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['order_number']) ?></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= htmlspecialchars($o['customer_phone']) ?></td>
              <td>৳<?= number_format($o['total'], 0) ?></td>
              <td><span class="badge <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
              <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
              <td><a href="order-detail.php?id=<?= $o['id'] ?>" class="btn">View</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>