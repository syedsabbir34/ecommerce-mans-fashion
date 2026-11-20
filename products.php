<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

$slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = $_GET['sort'] ?? 'latest';
$q    = trim($_GET['q'] ?? '');
$min  = ($_GET['min'] ?? '') !== '' ? max(0, (int)$_GET['min']) : null;
$max  = ($_GET['max'] ?? '') !== '' ? max(0, (int)$_GET['max']) : null;

$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 1";
$params = [];
if ($slug !== '') { $sql .= " AND c.slug = ?"; $params[] = $slug; }
if ($q !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)";
    $like = '%' . $q . '%'; array_push($params, $like, $like, $like);
}
if ($min !== null) { $sql .= " AND p.price >= ?"; $params[] = $min; }
if ($max !== null && $max > 0) { $sql .= " AND p.price <= ?"; $params[] = $max; }
if ($sort === 'sale') { $sql .= " AND p.old_price IS NOT NULL AND p.old_price > p.price"; }

switch ($sort) {
    case 'price_low':  $sql .= " ORDER BY p.price ASC"; break;
    case 'price_high': $sql .= " ORDER BY p.price DESC"; break;
    case 'new':        $sql .= " ORDER BY p.id DESC"; break;
    default:           $sql .= " ORDER BY (p.stock > 0) DESC, p.id DESC";
}
$st = $pdo->prepare($sql); $st->execute($params);
$products = $st->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC")->fetchAll();

$title = 'All products';
if ($slug !== '') foreach ($categories as $c) if ($c['slug'] === $slug) $title = $c['name'];
if ($sort === 'sale' && $slug === '') $title = 'Sale';
if ($sort === 'new' && $slug === '') $title = 'New arrivals';
if ($q !== '') $title = 'Results for “' . $q . '”';

function shopUrl(array $over = []): string {
    $base = array_filter(['category' => $_GET['category'] ?? '', 'sort' => $_GET['sort'] ?? '', 'q' => $_GET['q'] ?? '', 'min' => $_GET['min'] ?? '', 'max' => $_GET['max'] ?? ''], fn($v) => $v !== '');
    $m = array_filter(array_merge($base, $over), fn($v) => $v !== '' && $v !== null);
    return 'products.php' . ($m ? '?' . http_build_query($m) : '');
}

$page_title = $title . ' | ' . SITE_NAME;
include 'includes/header.php';
?>
<div class="page-head"><h1><?= e($title) ?></h1><p><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></p></div>

<div class="shop">
  <aside class="filter" aria-label="Filters">
    <h3>Categories</h3>
    <ul>
      <li><a href="<?= e(shopUrl(['category' => ''])) ?>" class="<?= $slug === '' ? 'on' : '' ?>">All products</a></li>
      <?php foreach ($categories as $c): ?>
        <li><a href="<?= e(shopUrl(['category' => $c['slug']])) ?>" class="<?= $slug === $c['slug'] ? 'on' : '' ?>"><?= e($c['name']) ?> <span><?= (int)$c['product_count'] ?></span></a></li>
      <?php endforeach; ?>
    </ul>
    <h3>Price (৳)</h3>
    <form method="GET" class="price-form">
      <?php foreach (['category','sort','q'] as $k) if (!empty($_GET[$k])): ?><input type="hidden" name="<?= $k ?>" value="<?= e($_GET[$k]) ?>"><?php endif; ?>
      <input type="number" name="min" placeholder="Min" min="0" value="<?= e($_GET['min'] ?? '') ?>" aria-label="Minimum price">
      <input type="number" name="max" placeholder="Max" min="0" value="<?= e($_GET['max'] ?? '') ?>" aria-label="Maximum price">
      <button class="btn btn-dark" style="padding:9px 14px">Go</button>
    </form>
    <?php if ($min !== null || $max !== null || $q !== ''): ?><a href="products.php" class="link-more" style="font-size:13px">Clear filters</a><?php endif; ?>
  </aside>

  <section>
    <div class="chips" aria-label="Categories">
      <a href="<?= e(shopUrl(['category' => ''])) ?>" class="<?= $slug === '' ? 'on' : '' ?>">All</a>
      <?php foreach ($categories as $c): ?><a href="<?= e(shopUrl(['category' => $c['slug']])) ?>" class="<?= $slug === $c['slug'] ? 'on' : '' ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
    </div>
    <div class="shop-bar">
      <span><?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?></span>
      <form method="GET">
        <?php foreach (['category','q','min','max'] as $k) if (!empty($_GET[$k])): ?><input type="hidden" name="<?= $k ?>" value="<?= e($_GET[$k]) ?>"><?php endif; ?>
        <label class="sr" style="position:absolute;left:-999px" for="sortSel">Sort by</label>
        <select name="sort" id="sortSel" onchange="this.form.submit()">
          <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Sort: Featured</option>
          <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Sort: Newest</option>
          <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
          <option value="sale" <?= $sort === 'sale' ? 'selected' : '' ?>>On sale only</option>
        </select>
      </form>
    </div>

    <?php if ($products): ?>
      <div class="pgrid"><?php foreach ($products as $p) renderProductCard($p); ?></div>
    <?php else: ?>
      <div class="empty">
        <h3>Nothing matched</h3>
        <p>Try a different word, or clear the filters. You can also ask us on WhatsApp and we will find it for you.</p>
        <a class="btn btn-dark" href="products.php">Show all products</a>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php include 'includes/footer.php'; ?>
