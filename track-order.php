<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

$num = strtoupper(trim($_REQUEST['order'] ?? ''));
$phoneIn = trim($_POST['phone'] ?? '');
$order = null; $items = []; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['track_tries'] = ($_SESSION['track_tries'] ?? 0) + 1;
    $phone = normalizePhone($phoneIn);
    if ($_SESSION['track_tries'] > 12) {
        $error = 'Too many attempts. Please wait a few minutes or message us on WhatsApp.';
    } elseif ($num === '' || !$phone) {
        $error = 'Enter your order number and the mobile number used for the order.';
    } else {
        $st = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND customer_phone = ?");
        $st->execute([$num, $phone]);
        $order = $st->fetch();
        if (!$order) $error = 'We could not find an order with those details. Check the number and try again.';
        else {
            $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $it->execute([$order['id']]);
            $items = $it->fetchAll();
        }
    }
}
$steps = ['pending' => 'Received', 'confirmed' => 'Confirmed', 'processing' => 'Packing', 'shipped' => 'On the way', 'delivered' => 'Delivered'];
$page_title = 'Track your order | ' . SITE_NAME;
include 'includes/header.php';
?>
<div class="center" style="max-width:720px">
  <h1>Track your order</h1>
  <p style="color:var(--muted);margin-bottom:26px">Enter your order number and the phone number you used at checkout.</p>

  <form method="POST" class="panel" style="text-align:left">
    <?php if ($error): ?><div class="errs" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="two">
      <div class="field"><label for="order">Order number</label><input type="text" id="order" name="order" placeholder="MF250101ABCDE" value="<?= e($num) ?>" required></div>
      <div class="field"><label for="phone">Mobile number</label><input type="tel" id="phone" name="phone" placeholder="01XXXXXXXXX" value="<?= e($phoneIn) ?>" required></div>
    </div>
    <button class="btn btn-dark btn-block">Track order</button>
  </form>

  <?php if ($order): ?>
    <div class="receipt">
      <?php if ($order['status'] === 'cancelled'): ?>
        <p><b>This order was cancelled.</b> If this is a mistake, message us on WhatsApp.</p>
      <?php else:
        $keys = array_keys($steps); $at = array_search($order['status'], $keys, true); ?>
        <div class="timeline">
          <?php foreach ($steps as $k => $label): $i = array_search($k, $keys, true); ?>
            <div class="<?= $i <= $at ? 'done' : '' ?>"><i></i><?= e($label) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div style="margin-top:22px">
        <?php foreach ($items as $i): ?>
          <div class="row"><span><?= e($i['product_name']) ?><?= !empty($i['size']) ? ' (' . e($i['size']) . ')' : '' ?> × <?= (int)$i['quantity'] ?></span><span><?= money($i['line_total']) ?></span></div>
        <?php endforeach; ?>
        <div class="row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
        <div class="row" style="margin-top:10px"><span>Placed on</span><span><?= date('d M Y, g:i A', strtotime($order['created_at'])) ?></span></div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
