<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

$num = trim($_GET['order'] ?? '');
$order = null; $items = [];
// Only the browser that placed the order can see the full details
if ($num !== '' && ($_SESSION['last_order'] ?? '') === $num) {
    $st = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $st->execute([$num]);
    $order = $st->fetch();
    if ($order) {
        $it = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $it->execute([$order['id']]);
        $items = $it->fetchAll();
    }
}
$page_title = 'Order placed | ' . SITE_NAME;
include 'includes/header.php';
?>
<div class="center">
<?php if ($order): ?>
  <div class="check"><?= icon('check', 38) ?></div>
  <h1>Thank you, <?= e(explode(' ', $order['customer_name'])[0]) ?>. Your order is in.</h1>
  <p style="color:var(--muted)">Your order number is <b style="color:var(--ink)"><?= e($order['order_number']) ?></b>. Please save it.</p>

  <div class="receipt">
    <?php foreach ($items as $i): ?>
      <div class="row"><span><?= e($i['product_name']) ?><?= !empty($i['size']) ? ' (' . e($i['size']) . ')' : '' ?> × <?= (int)$i['quantity'] ?></span><span><?= money($i['line_total']) ?></span></div>
    <?php endforeach; ?>
    <div class="row"><span>Delivery</span><span><?= $order['shipping'] > 0 ? money($order['shipping']) : 'Free' ?></span></div>
    <div class="row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
    <div class="row" style="margin-top:12px"><span>Payment</span><span><?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : strtoupper($order['payment_method']) . ' · ' . e($order['payment_trx']) ?></span></div>
    <div class="row"><span>Deliver to</span><span style="text-align:right;max-width:60%"><?= e($order['customer_address']) ?>, <?= e($order['customer_city']) ?></span></div>
  </div>

  <ol class="next-steps">
    <li>We will call or message <b><?= e($order['customer_phone']) ?></b> to confirm your order.</li>
    <li>We pack and ship it. Delivery takes <?= $order['shipping_zone'] === 'outside' ? '2–5' : '1–2' ?> working days.</li>
    <li><?= $order['payment_method'] === 'cod' ? 'Pay in cash when it arrives.' : 'We verify your payment while confirming.' ?></li>
  </ol>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a class="btn btn-dark" href="track-order.php?order=<?= e($order['order_number']) ?>">Track this order</a>
    <a class="btn btn-line" target="_blank" rel="noopener" href="<?= e(waLink('Hi Mans Fashion, my order number is ' . $order['order_number'])) ?>">Message us on WhatsApp</a>
  </div>
<?php else: ?>
  <h1>Looking for your order?</h1>
  <p style="color:var(--muted);margin-bottom:22px">For your privacy, order details are shown only on the device that placed the order. Use order tracking with your order number and phone.</p>
  <a class="btn btn-dark" href="track-order.php<?= $num !== '' ? '?order=' . e($num) : '' ?>">Track an order</a>
<?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
