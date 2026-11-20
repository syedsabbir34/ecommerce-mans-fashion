<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: products.php"); exit; }

$st = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 1");
$st->execute([$id]);
$p = $st->fetch();
if (!$p) { header("Location: products.php"); exit; }

$revErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rn = trim($_POST['rname'] ?? '');
    $rr = (int)($_POST['rating'] ?? 0);
    $rc = trim($_POST['rcomment'] ?? '');
    if (!csrfOk()) $revErrors[] = 'Your session expired. Please reload the page and try again.';
    if (!empty($_POST['website'])) $revErrors[] = 'Could not post your review.';
    if (time() - ($_SESSION['last_review'] ?? 0) < 30) $revErrors[] = 'Please wait a few seconds before posting again.';
    if (mb_strlen($rn) < 2 || mb_strlen($rn) > 60) $revErrors[] = 'Please enter your name (2–60 characters).';
    if ($rr < 1 || $rr > 5) $revErrors[] = 'Please choose a star rating.';
    if (mb_strlen($rc) < 3 || mb_strlen($rc) > 1000) $revErrors[] = 'Please write your review (3–1000 characters).';
    if (!$revErrors) {
        $pdo->prepare("INSERT INTO reviews (product_id, name, rating, comment) VALUES (?,?,?,?)")->execute([$id, $rn, $rr, $rc]);
        $_SESSION['last_review'] = time();
        $_SESSION['flash_ok'] = 'Thank you! Your review has been posted.';
        header("Location: product.php?id=$id#reviews"); exit;
    }
}
$rv = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY id DESC");
$rv->execute([$id]);
$reviews = $rv->fetchAll();
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

$rel = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? AND p.status = 1 ORDER BY RAND() LIMIT 4");
$rel->execute([$p['category_id'], $id]);
$related = $rel->fetchAll();

$sizes = productSizes($p);
$stock = (int)$p['stock'];
$pct = discountPercent($p);
$page_title = $p['name'] . ' | ' . SITE_NAME;
$page_desc = mb_substr(strip_tags((string)$p['description']), 0, 150);
$waText = "Hi Mans Fashion, I want to order: {$p['name']} (" . money($p['price']) . "). Link: " . (isset($_SERVER['HTTP_HOST']) ? ((!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . "/product.php?id=$id") : '');
include 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb">
  <a href="index.php">Home</a> / <a href="products.php">Shop</a>
  <?php if ($p['category_slug']): ?> / <a href="products.php?category=<?= e($p['category_slug']) ?>"><?= e($p['category_name']) ?></a><?php endif; ?>
</nav>

<div class="pd">
  <div class="pd-gallery"><img src="<?= e(productImage($p['image'], 1000)) ?>" alt="<?= e($p['name']) ?>"></div>

  <div>
    <?php if ($p['category_name']): ?><div class="pd-cat"><?= e($p['category_name']) ?></div><?php endif; ?>
    <h1><?= e($p['name']) ?></h1>
    <div class="pd-price"><?= money($p['price']) ?>
      <?php if (!empty($p['old_price'])): ?><s><?= money($p['old_price']) ?></s><?php endif; ?>
      <?php if ($pct): ?><span class="pd-save">Save <?= $pct ?>%</span><?php endif; ?>
    </div>
    <?php if ($stock < 1): ?><div class="pd-stock out">Sold out</div>
    <?php elseif ($stock <= 5): ?><div class="pd-stock low">Only <?= $stock ?> left</div>
    <?php else: ?><div class="pd-stock">In stock</div><?php endif; ?>

    <p class="pd-desc"><?= nl2br(e($p['description'] ?: 'Quality menswear from Mans Fashion.')) ?></p>

    <form id="buyForm" data-id="<?= $id ?>" data-needs-size="<?= $sizes ? '1' : '0' ?>">
      <input type="hidden" name="size" value="">
      <?php if ($sizes): ?>
        <div class="opt-label"><span>Size</span><a href="size-guide.php" target="_blank">Size guide</a></div>
        <div class="sizes" role="radiogroup" aria-label="Size">
          <?php foreach ($sizes as $s): ?>
            <button type="button" class="size-opt" role="radio" aria-checked="false" data-size="<?= e($s) ?>"><?= e($s) ?></button>
          <?php endforeach; ?>
        </div>
        <div id="sizeMsg" aria-live="polite"></div>
      <?php endif; ?>

      <?php if ($stock > 0): ?>
        <div class="buy-row">
          <div class="stepper">
            <button type="button" data-step="-1" aria-label="Decrease quantity">−</button>
            <input type="number" name="quantity" value="1" min="1" max="<?= $stock ?>" aria-label="Quantity">
            <button type="button" data-step="1" aria-label="Increase quantity">+</button>
          </div>
          <button type="submit" class="btn btn-line">Add to cart</button>
          <button type="submit" class="btn btn-dark" data-go="1">Buy now</button>
        </div>
      <?php else: ?>
        <button type="button" class="btn btn-block btn-line" disabled>Sold out</button>
      <?php endif; ?>
    </form>

    <a class="wa-order" href="<?= e(waLink($waText)) ?>" target="_blank" rel="noopener"><?= icon('wa', 20) ?> Order or ask a question on WhatsApp</a>

    <div class="perks">
      <div><?= icon('truck', 22) ?><span><b>Delivery in 2–5 days.</b> <?= money(SHIPPING_INSIDE) ?> inside <?= e(HOME_ZONE_LABEL) ?>, <?= money(SHIPPING_OUTSIDE) ?> elsewhere. Free over <?= money(FREE_SHIPPING_MIN) ?>.</span></div>
      <div><?= icon('cash', 22) ?><span><b>Cash on delivery</b> available. bKash and Nagad also accepted.</span></div>
      <div><?= icon('swap', 22) ?><span><b>7-day exchange</b> for unused items with tags. <a href="shipping-returns.php" style="text-decoration:underline">Details</a></span></div>
    </div>
  </div>
</div>

<section class="reviews" id="reviews">
  <div class="section-head"><div><h2>Customer reviews</h2>
    <p><?php if ($reviews): ?><span class="stars"><?= str_repeat('★', (int)round($avg)) . str_repeat('☆', 5 - (int)round($avg)) ?></span> <?= number_format($avg, 1) ?> out of 5 · <?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?><?php else: ?>No reviews yet. Be the first to share your experience.<?php endif; ?></p></div></div>
  <div class="rev-grid">
    <div>
      <?php foreach ($reviews as $r): ?>
        <article class="rev">
          <div class="rev-top"><b><?= e($r['name']) ?></b><span class="stars" aria-label="<?= (int)$r['rating'] ?> out of 5"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span><time><?= date('d M Y', strtotime($r['created_at'])) ?></time></div>
          <p><?= nl2br(e($r['comment'])) ?></p>
          <?php if (!empty($r['admin_reply'])): ?>
            <div class="rev-reply"><b>Reply from <?= e(SITE_NAME) ?></b><p><?= nl2br(e($r['admin_reply'])) ?></p></div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <form method="POST" action="product.php?id=<?= $id ?>#reviews" class="panel rev-form">
      <h2>Write a review</h2>
      <?php if ($revErrors): ?><div class="errs" role="alert"><ul><?php foreach ($revErrors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <?= csrfField() ?>
      <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
      <div class="field"><label>Your rating</label>
        <div class="rate" role="radiogroup" aria-label="Rating">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" id="rt<?= $i ?>" value="<?= $i ?>" <?= (int)($_POST['rating'] ?? 0) === $i ? 'checked' : '' ?>><label for="rt<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="field"><label for="rname">Your name</label><input type="text" id="rname" name="rname" maxlength="60" value="<?= e($_POST['rname'] ?? '') ?>" required></div>
      <div class="field"><label for="rcomment">Your review</label><textarea id="rcomment" name="rcomment" rows="4" maxlength="1000" placeholder="Share your experience. You can write in English or বাংলা." required><?= e($_POST['rcomment'] ?? '') ?></textarea></div>
      <button type="submit" name="submit_review" class="btn btn-dark btn-block">Post review</button>
    </form>
  </div>
</section>

<?php if ($related): ?>
<section class="related">
  <div class="section-head"><div><h2>You may also like</h2></div></div>
  <div class="pgrid"><?php foreach ($related as $r) renderProductCard($r); ?></div>
</section>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
