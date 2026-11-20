<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY id ASC")->fetchAll();
$sel = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 1";
$newest = $pdo->query("$sel ORDER BY p.id DESC LIMIT 8")->fetchAll();
$onSale = $pdo->query("$sel AND p.old_price IS NOT NULL AND p.old_price > p.price ORDER BY (p.old_price - p.price) / p.old_price DESC LIMIT 4")->fetchAll();

$cat_images = [
  'shirts'      => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=700',
  'pants'       => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?q=80&w=700',
  'jackets'     => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?q=80&w=700',
  'accessories' => 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?q=80&w=700',
];
$fallback = 'https://images.unsplash.com/photo-1617137968427-85924c800a22?q=80&w=700';

$page_title = SITE_NAME . ' | Premium Men\'s Fashion in Bangladesh';
include 'includes/header.php';
?>
<section class="hero">
  <div class="hero-inner">
    <div class="hero-copy">
      <h1>Dress sharp. Pay on delivery.</h1>
      <p>Shirts, pants, jackets and accessories made to look good and last. Order online and pay when it reaches your door.</p>
      <div class="hero-actions">
        <a href="products.php" class="btn btn-primary">Shop all products</a>
        <a href="products.php?sort=sale" class="btn btn-outline">See sale items</a>
      </div>
    </div>
    <div class="hero-media" role="img" aria-label="Man wearing a tailored outfit">
      <div class="hero-note"><b>Free delivery</b>on orders over <?= money(FREE_SHIPPING_MIN) ?></div>
    </div>
  </div>
</section>

<section class="trust" aria-label="Why shop with us">
  <div class="trust-inner">
    <div class="trust-item"><?= icon('truck') ?><div><b>Delivery all over Bangladesh</b><span>2–5 working days</span></div></div>
    <div class="trust-item"><?= icon('cash') ?><div><b>Cash on delivery</b><span>Check it, then pay</span></div></div>
    <div class="trust-item"><?= icon('swap') ?><div><b>7-day exchange</b><span>Wrong size? We swap it</span></div></div>
    <div class="trust-item"><?= icon('chat') ?><div><b>Real people on WhatsApp</b><span><?= e(STORE_HOURS) ?></span></div></div>
  </div>
</section>

<section class="section">
  <div class="section-head"><div><h2>Shop by category</h2></div><a class="link-more" href="products.php">View everything</a></div>
  <div class="cats">
    <?php foreach ($categories as $cat): ?>
      <a class="cat" href="products.php?category=<?= e($cat['slug']) ?>">
        <img src="<?= e($cat_images[$cat['slug']] ?? $fallback) ?>" alt="" loading="lazy">
        <div class="cat-label"><h3><?= e($cat['name']) ?></h3><span><?= (int)$cat['product_count'] ?> items</span></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <div class="section-head"><div><h2>New arrivals</h2><p>Fresh in this month.</p></div><a class="link-more" href="products.php?sort=new">Shop new arrivals</a></div>
  <div class="pgrid"><?php foreach ($newest as $p) renderProductCard($p); ?></div>
</section>

<?php if ($onSale): ?>
<div class="banner">
  <div class="banner-in">
    <div><h2>Up to <?= max(array_map('discountPercent', $onSale)) ?>% off right now</h2><p>Limited stock on sale styles. Once a size is gone, it is gone.</p></div>
    <a href="products.php?sort=sale" class="btn btn-primary">Shop the sale</a>
  </div>
</div>
<section class="section">
  <div class="pgrid"><?php foreach ($onSale as $p) renderProductCard($p); ?></div>
</section>
<?php endif; ?>

<section class="section">
  <div class="section-head"><div><h2>How ordering works</h2></div></div>
  <div class="how">
    <div><h3>Pick your size</h3><p>Choose a product and size. Not sure? Use the <a href="size-guide.php" style="text-decoration:underline">size guide</a> or ask our assistant.</p></div>
    <div><h3>Confirm your details</h3><p>Enter your name, phone and address. We call or message to confirm your order.</p></div>
    <div><h3>Pay when it arrives</h3><p>Pay cash on delivery, or by bKash or Nagad if you prefer. Exchange within 7 days.</p></div>
  </div>
</section>
<?php include 'includes/footer.php'; ?>
