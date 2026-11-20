<?php
session_start();
require_once '../config/database.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','confirmed','processing','shipped','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
    }
    header('Location: orders.php');
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orders - Admin</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Segoe UI', sans-serif; background:#f5f5f5; }
    .sidebar { position:fixed; left:0; top:0; bottom:0; width:220px; background:#0f0f0f; color:white; padding:30px 20px; }
    .sidebar h2 { font-size:18px; margin-bottom:30px; }
    .sidebar a { display:block; color:#ccc; padding:10px 0; text-decoration:none; font-size:14px; }
    .sidebar a:hover { color:#c9a227; }
    .main { margin-left:220px; padding:30px; }
    table { width:100%; background:white; border-collapse:collapse; border-radius:8px; overflow:hidden; }
    th, td { padding:14px 16px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th { background:#fafafa; font-weight:600; font-size:12px; text-transform:uppercase; }
    select { padding:6px 10px; border:1px solid #ddd; }
    .badge { padding:4px 10px; border-radius:20px; font-size:12px; }
    .badge.pending { background:#fff3cd; color:#856404; }
    .badge.confirmed { background:#d4edda; color:#155724; }
    .badge.processing { background:#e2e3e5; color:#383d41; }
    .badge.shipped { background:#cce5ff; color:#004085; }
    .badge.delivered { background:#d1e7dd; color:#0f5132; }
    .badge.cancelled { background:#f8d7da; color:#721c24; }
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
    <a href="index.php?logout=1" style="margin-top:40px;color:#e74c3c;">Logout</a>
  </div>

  <div class="main">
    <h1 style="margin-bottom:30px;">All Orders</h1>
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Phone</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="8" style="text-align:center;padding:30px;color:#888;">No orders yet</td></tr>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= htmlspecialchars($o['order_number']) ?></td>
              <td><?= htmlspecialchars($o['customer_name']) ?></td>
              <td><?= htmlspecialchars($o['customer_phone']) ?></td>
              <td>৳<?= number_format($o['total'], 0) ?></td>
              <td><?= strtoupper($o['payment_method']) ?></td>
              <td><span class="badge <?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
              <td><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <select name="status" onchange="this.form.submit()">
                    <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="update_status" value="1">
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>