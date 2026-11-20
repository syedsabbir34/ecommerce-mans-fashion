<?php
session_start();
require_once '../config/database.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if ($order && isset($_POST['update_status'])) {
    $allowed = ['pending','confirmed','processing','shipped','delivered','cancelled'];
    if (in_array($_POST['status'] ?? '', $allowed, true)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$_POST['status'], $id]);
        header('Location: order-detail.php?id=' . $id); exit;
    }
}

if (!$order) {
    header('Location: orders.php');
    exit;
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$order_items = $items->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Order Detail - Admin</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Segoe UI', sans-serif; background:#f5f5f5; }
    .sidebar { position:fixed; left:0; top:0; bottom:0; width:220px; background:#0f0f0f; color:white; padding:30px 20px; }
    .sidebar h2 { font-size:18px; margin-bottom:30px; }
    .sidebar a { display:block; color:#ccc; padding:10px 0; text-decoration:none; font-size:14px; }
    .sidebar a:hover { color:#c9a227; }
    .main { margin-left:220px; padding:30px; }
    .card { background:white; padding:24px; border-radius:8px; margin-bottom:20px; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th { background:#fafafa; }
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
    <h1 style="margin-bottom:20px;">Order #<?= htmlspecialchars($order['order_number']) ?></h1>

    <div class="card">
      <h3 style="margin-bottom:16px;">Customer Info</h3>
      <p><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
      <p><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($order['customer_email'] ?? '-') ?></p>
      <p><strong>Address:</strong> <?= htmlspecialchars($order['customer_address']) ?>, <?= htmlspecialchars($order['customer_city']) ?></p>
      <p><strong>Payment:</strong> <?= strtoupper($order['payment_method']) ?><?= !empty($order['payment_trx']) ? ' — TrxID: ' . htmlspecialchars($order['payment_trx']) : '' ?></p>
      <p><strong>Delivery area:</strong> <?= htmlspecialchars($order['shipping_zone'] ?? '-') ?></p>
      <p><strong>Placed:</strong> <?= date('d M Y, g:i A', strtotime($order['created_at'])) ?></p>
      <form method="POST" style="margin:12px 0;display:flex;gap:8px;align-items:center">
        <strong>Status:</strong>
        <select name="status" style="padding:6px 10px;border:1px solid #ddd">
          <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" name="update_status" style="padding:7px 16px;background:#0f0f0f;color:#fff;border:0;cursor:pointer">Update</button>
      </form>
      <?php $wa = '88' . $order['customer_phone']; ?>
      <p><a href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode('Hello ' . $order['customer_name'] . ', this is Mans Fashion regarding your order ' . $order['order_number'] . '.') ?>" target="_blank" style="color:#128c4a;font-weight:600">Message customer on WhatsApp</a></p>
      <p><strong>Notes:</strong> <?= htmlspecialchars($order['notes'] ?? '-') ?></p>
    </div>

    <div class="card">
      <h3 style="margin-bottom:16px;">Order Items</h3>
      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>Size</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($order_items as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['product_name']) ?></td>
              <td><?= htmlspecialchars($item['size'] ?? '-') ?></td>
              <td><?= htmlspecialchars($item['size'] ?? '-') ?></td>
              <td>৳<?= number_format($item['price'], 0) ?></td>
              <td><?= $item['quantity'] ?></td>
              <td>৳<?= number_format($item['line_total'], 0) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p style="margin-top:16px">Subtotal: ৳<?= number_format($order['subtotal'], 0) ?> · Delivery: ৳<?= number_format($order['shipping'], 0) ?></p>
      <p style="margin-top:8px;font-size:18px;font-weight:600;">
        Grand Total: ৳<?= number_format($order['total'], 0) ?>
      </p>
    </div>

    <a href="orders.php" style="color:#0f0f0f;">← Back to Orders</a>
  </div>
</body>
</html>