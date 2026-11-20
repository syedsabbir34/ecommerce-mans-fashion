<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

$items = getCartItems($pdo);
if (!$items) { header("Location: cart.php"); exit; }
$subtotal = cartSubtotal($items);
$errors = [];
$zone = ($_POST['zone'] ?? 'inside') === 'outside' ? 'outside' : 'inside';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $phoneIn = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    $payment = in_array($_POST['payment_method'] ?? 'cod', ['cod', 'bkash', 'nagad'], true) ? $_POST['payment_method'] : 'cod';
    $trx     = strtoupper(trim($_POST['trx'] ?? ''));
    $phone   = normalizePhone($phoneIn);

    if (mb_strlen($name) < 2) $errors[] = 'Please enter your full name.';
    if (!$phone) $errors[] = 'Enter a valid Bangladesh mobile number, for example 01712345678.';
    if (mb_strlen($address) < 8) $errors[] = 'Please enter your full delivery address (house, road, area).';
    if ($city === '') $errors[] = 'Please enter your city or district.';
    if ($payment !== 'cod' && !preg_match('/^[A-Z0-9]{6,20}$/', $trx)) $errors[] = 'Enter the ' . ($payment === 'bkash' ? 'bKash' : 'Nagad') . ' transaction ID (letters and numbers, 6–20 characters).';

    // Re-check stock and sizes
    foreach ($items as $it) {
        $p = $it['product'];
        $sizes = productSizes($p);
        if ($sizes && !in_array($it['size'], $sizes, true)) $errors[] = $p['name'] . ': please choose a size again.';
        $total_for_product = 0;
        foreach ($items as $o) if ($o['product']['id'] === $p['id']) $total_for_product += $o['quantity'];
        if ($total_for_product > $p['stock']) $errors[] = $p['name'] . ' has only ' . (int)$p['stock'] . ' left. Please update your cart.';
    }

    if (!$errors) {
        $shipping = shippingCost($subtotal, $zone);
        $total = $subtotal + $shipping;
        try {
            $pdo->beginTransaction();
            $order_number = 'MF' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $pdo->prepare("INSERT INTO orders (order_number, customer_name, customer_phone, customer_email, customer_address, customer_city, shipping_zone, subtotal, shipping, total, payment_method, payment_trx, notes, status)
                           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, 'pending')")
                ->execute([$order_number, $name, $phone, null, $address, $city, $zone, $subtotal, $shipping, $total, $payment, $payment === 'cod' ? null : $trx, $notes ?: null]);
            $order_id = $pdo->lastInsertId();

            $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, size, price, quantity, line_total) VALUES (?,?,?,?,?,?,?)");
            $dec = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            foreach ($items as $it) {
                $p = $it['product'];
                $dec->execute([$it['quantity'], $p['id'], $it['quantity']]);
                if ($dec->rowCount() === 0) throw new RuntimeException('stock');
                $ins->execute([$order_id, $p['id'], $p['name'], $it['size'] ?: null, $p['price'], $it['quantity'], $it['line_total']]);
            }
            $pdo->commit();
            clearCart();
            $_SESSION['last_order'] = $order_number;
            header("Location: order-success.php?order=" . urlencode($order_number));
            exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $ex->getMessage() === 'stock'
                ? 'One of your items just sold out. Please review your cart.'
                : 'We could not place your order. Please try again, or order on WhatsApp.';
        }
    }
}

$shipNow = shippingCost($subtotal, $zone);
$page_title = 'Checkout | ' . SITE_NAME;
include 'includes/header.php';
$v = fn($k) => e($_POST[$k] ?? '');
$payment_sel = $_POST['payment_method'] ?? 'cod';
?>
<div class="wrap">
  <h1>Checkout</h1>
  <?php if ($errors): ?>
    <div class="errs" role="alert"><b>Please fix the following:</b><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="POST" id="checkoutForm" novalidate
        data-subtotal="<?= $subtotal ?>" data-inside="<?= SHIPPING_INSIDE ?>" data-outside="<?= SHIPPING_OUTSIDE ?>" data-free="<?= FREE_SHIPPING_MIN ?>"
        data-bkash="<?= e(BKASH_NUMBER) ?>" data-nagad="<?= e(NAGAD_NUMBER) ?>">
    <div class="co-grid">
      <div>
        <div class="panel">
          <h2>Delivery details</h2>
          <div class="field"><label for="name">Full name</label><input type="text" id="name" name="name" autocomplete="name" value="<?= $v('name') ?>" required></div>
          <div class="field"><label for="phone">Mobile number</label><input type="tel" id="phone" name="phone" inputmode="numeric" autocomplete="tel" placeholder="01XXXXXXXXX" value="<?= $v('phone') ?>" required><small>We call or message this number to confirm your order.</small></div>
          <div class="field"><label for="address">Full address</label><textarea id="address" name="address" rows="3" autocomplete="street-address" placeholder="House, road, area" required><?= $v('address') ?></textarea></div>
          <div class="field"><label for="city">City / district</label><input type="text" id="city" name="city" autocomplete="address-level2" value="<?= $v('city') ?>" required></div>
          <div class="field"><label for="notes">Note for us (optional)</label><textarea id="notes" name="notes" rows="2" placeholder="Landmark, preferred delivery time…"><?= $v('notes') ?></textarea></div>
        </div>

        <div class="panel">
          <h2>Delivery area</h2>
          <label class="choice"><input type="radio" name="zone" value="inside" <?= $zone === 'inside' ? 'checked' : '' ?>><div><b>Inside <?= e(HOME_ZONE_LABEL) ?></b><span>1–2 working days</span></div><em><?= money(SHIPPING_INSIDE) ?></em></label>
          <label class="choice"><input type="radio" name="zone" value="outside" <?= $zone === 'outside' ? 'checked' : '' ?>><div><b>Outside <?= e(HOME_ZONE_LABEL) ?></b><span>2–5 working days</span></div><em><?= money(SHIPPING_OUTSIDE) ?></em></label>
          <?php if ($subtotal >= FREE_SHIPPING_MIN): ?><p class="mini-note" style="text-align:left">Your order qualifies for free delivery.</p><?php endif; ?>
        </div>

        <div class="panel">
          <h2>Payment</h2>
          <label class="choice"><input type="radio" name="payment_method" value="cod" <?= $payment_sel === 'cod' ? 'checked' : '' ?>><div><b>Cash on delivery</b><span>Pay in cash when you receive your order.</span></div></label>
          <label class="choice"><input type="radio" name="payment_method" value="bkash" <?= $payment_sel === 'bkash' ? 'checked' : '' ?>><div><b>bKash</b><span>Send money now, enter the transaction ID.</span></div></label>
          <label class="choice"><input type="radio" name="payment_method" value="nagad" <?= $payment_sel === 'nagad' ? 'checked' : '' ?>><div><b>Nagad</b><span>Send money now, enter the transaction ID.</span></div></label>
          <div id="trxBox" hidden>
            <div class="pay-note" id="payNote"></div>
            <div class="field"><label for="trx">Transaction ID</label><input type="text" id="trx" name="trx" autocomplete="off" placeholder="e.g. 9H7K2LMN4P" value="<?= $v('trx') ?>"></div>
          </div>
        </div>
      </div>

      <aside class="box">
        <h2>Your order</h2>
        <?php foreach ($items as $it): $p = $it['product']; ?>
          <div class="sum-item">
            <img src="<?= e(productImage($p['image'], 200)) ?>" alt="">
            <div><?= e($p['name']) ?><br><small><?= $it['size'] !== '' ? 'Size ' . e($it['size']) . ' · ' : '' ?>Qty <?= $it['quantity'] ?></small></div>
            <b><?= money($it['line_total']) ?></b>
          </div>
        <?php endforeach; ?>
        <div style="border-top:1px solid #d5d8dd;margin-top:14px;padding-top:14px">
          <div class="row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
          <div class="row"><span>Delivery</span><span id="shipVal"><?= $shipNow == 0 ? 'Free' : money($shipNow) ?></span></div>
          <div class="row total"><span>Total</span><span id="totalVal"><?= money($subtotal + $shipNow) ?></span></div>
        </div>
        <button type="submit" id="placeBtn" class="btn btn-dark btn-block">Place order</button>
        <p class="mini-note">By placing your order you agree to our <a href="shipping-returns.php" style="text-decoration:underline">delivery and return policy</a>.</p>
      </aside>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
