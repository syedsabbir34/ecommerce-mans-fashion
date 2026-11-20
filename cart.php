<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item'])) removeFromCart((string)$_POST['remove_item']);
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $key => $qty) updateCartQuantity((string)$key, (int)$qty);
    }
    header("Location: cart.php"); exit;
}

$items = getCartItems($pdo);
$subtotal = cartSubtotal($items);
$remaining = max(0, FREE_SHIPPING_MIN - $subtotal);
$pct = min(100, (int)round($subtotal / FREE_SHIPPING_MIN * 100));

$page_title = 'Your cart | ' . SITE_NAME;
include 'includes/header.php';
?>
<div class="wrap">
  <h1>Your cart</h1>
  <?php if (!$items): ?>
    <div class="empty">
      <h3>Your cart is empty</h3>
      <p>Add something you like and it will show up here.</p>
      <a class="btn btn-dark" href="products.php">Start shopping</a>
    </div>
  <?php else: ?>
  <div class="cart-grid">
    <form method="POST" id="cartForm">
      <?php if ($remaining > 0): ?>
        <div class="free-bar">Add <b><?= money($remaining) ?></b> more to get free delivery.<div class="meter"><i style="width:<?= $pct ?>%"></i></div></div>
      <?php else: ?>
        <div class="free-bar done">You have free delivery on this order.</div>
      <?php endif; ?>

      <?php foreach ($items as $it): $p = $it['product']; ?>
        <div class="line">
          <a href="product.php?id=<?= (int)$p['id'] ?>"><img src="<?= e(productImage($p['image'], 300)) ?>" alt="<?= e($p['name']) ?>"></a>
          <div>
            <h3><a href="product.php?id=<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a></h3>
            <div class="meta"><?= $it['size'] !== '' ? 'Size: ' . e($it['size']) . ' · ' : '' ?><?= money($p['price']) ?> each</div>
            <div class="stepper">
              <button type="button" class="cart-step" data-step="-1" aria-label="Decrease quantity">−</button>
              <input type="number" name="quantity[<?= e($it['key']) ?>]" value="<?= $it['quantity'] ?>" min="1" max="<?= (int)$p['stock'] ?>" aria-label="Quantity" onchange="this.form.submit()">
              <button type="button" class="cart-step" data-step="1" aria-label="Increase quantity">+</button>
            </div>
            <button type="submit" name="remove_item" value="<?= e($it['key']) ?>" class="rm">Remove</button>
          </div>
          <div class="lt"><?= money($it['line_total']) ?></div>
        </div>
      <?php endforeach; ?>
      <p style="margin-top:18px"><a href="products.php" class="link-more">Continue shopping</a></p>
    </form>

    <aside class="box">
      <h2>Order summary</h2>
      <div class="row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <div class="row"><span>Delivery</span><span><?= $remaining > 0 ? 'Calculated at checkout' : 'Free' ?></span></div>
      <div class="row total"><span>Total</span><span><?= money($subtotal) ?></span></div>
      <a href="checkout.php" class="btn btn-dark btn-block">Checkout</a>
      <p class="mini-note">Cash on delivery available. No account needed.</p>
    </aside>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
