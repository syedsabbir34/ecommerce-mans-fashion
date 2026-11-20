<?php
session_start();
require_once '../config/database.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$success = '';
$error   = '';
$edit_product = null;

// ========== LOAD PRODUCT FOR EDIT ==========
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
    if (!$edit_product) {
        $error = 'Product not found.';
    }
}

// ========== DELETE PRODUCT ==========
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT category_id FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();

        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

        if ($prod && $prod['category_id']) {
            $pdo->prepare("UPDATE categories SET product_count = (
                SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 1
            ) WHERE id = ?")->execute([$prod['category_id'], $prod['category_id']]);
        }
        header('Location: products.php?msg=deleted');
        exit;
    }
}

// ========== UPDATE PRODUCT ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $old_price   = $_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null;
    $stock       = (int)($_POST['stock'] ?? 0);
    $image       = trim($_POST['image'] ?? '');
    $badge       = trim($_POST['badge'] ?? '') ?: null;
    $sizes       = trim(preg_replace('/\s*,\s*/', ',', $_POST['sizes'] ?? ''), ', ') ?: null;
    $status      = isset($_POST['status']) ? 1 : 0;

    if ($slug === '' && $name !== '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    if ($id <= 0 || $name === '' || $price <= 0) {
        $error = 'Name and Price are required.';
        // reload edit form
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $edit_product = $stmt->fetch();
    } else {
        // unique slug (ignore self)
        $check = $pdo->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $check->execute([$slug, $id]);
        if ($check->fetch()) {
            $slug .= '-' . time();
        }

        // old category for count update
        $old = $pdo->prepare("SELECT category_id FROM products WHERE id = ?");
        $old->execute([$id]);
        $old_cat = $old->fetchColumn();

        $stmt = $pdo->prepare("UPDATE products SET
            category_id = ?, name = ?, slug = ?, description = ?,
            price = ?, old_price = ?, stock = ?, sizes = ?, image = ?, badge = ?, status = ?
            WHERE id = ?");
        $stmt->execute([
            $category_id ?: null,
            $name,
            $slug,
            $description ?: null,
            $price,
            $old_price,
            $stock,
            $sizes,
            $image ?: null,
            $badge,
            $status,
            $id
        ]);

        // update category counts
        $cats_to_update = array_unique(array_filter([$old_cat, $category_id]));
        foreach ($cats_to_update as $cid) {
            $pdo->prepare("UPDATE categories SET product_count = (
                SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 1
            ) WHERE id = ?")->execute([$cid, $cid]);
        }

        header('Location: products.php?msg=updated');
        exit;
    }
}

// ========== ADD PRODUCT ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name        = trim($_POST['name'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $old_price   = $_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null;
    $stock       = (int)($_POST['stock'] ?? 0);
    $image       = trim($_POST['image'] ?? '');
    $badge       = trim($_POST['badge'] ?? '') ?: null;
    $sizes       = trim(preg_replace('/\s*,\s*/', ',', $_POST['sizes'] ?? ''), ', ') ?: null;
    $status      = isset($_POST['status']) ? 1 : 0;

    if ($slug === '' && $name !== '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = trim($slug, '-');
    }

    if ($name === '' || $price <= 0) {
        $error = 'Name and Price are required.';
    } else {
        $check = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            $slug .= '-' . time();
        }

        $stmt = $pdo->prepare("INSERT INTO products
            (category_id, name, slug, description, price, old_price, stock, sizes, image, badge, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $category_id ?: null, $name, $slug, $description ?: null,
            $price, $old_price, $stock, $sizes, $image ?: null, $badge, $status
        ]);

        if ($category_id > 0) {
            $pdo->prepare("UPDATE categories SET product_count = (
                SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 1
            ) WHERE id = ?")->execute([$category_id, $category_id]);
        }

        header('Location: products.php?msg=added');
        exit;
    }
}

// Flash messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added')   $success = 'Product added successfully!';
    if ($_GET['msg'] === 'updated') $success = 'Product updated successfully!';
    if ($_GET['msg'] === 'deleted') $success = 'Product deleted successfully.';
}

$products   = $pdo->query("SELECT p.*, c.name AS category_name
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.id
                           ORDER BY p.id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status = 1 ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Products - Admin</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Segoe UI', sans-serif; background:#f5f5f5; }
    .sidebar { position:fixed; left:0; top:0; bottom:0; width:220px; background:#0f0f0f; color:white; padding:30px 20px; }
    .sidebar h2 { font-size:18px; margin-bottom:30px; }
    .sidebar a { display:block; color:#ccc; padding:10px 0; text-decoration:none; font-size:14px; }
    .sidebar a:hover { color:#c9a227; }
    .main { margin-left:220px; padding:30px; }
    .alert { padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:14px; }
    .alert-success { background:#d4edda; color:#155724; }
    .alert-error { background:#f8d7da; color:#721c24; }
    .card { background:white; padding:24px; border-radius:8px; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
    .card h2 { font-size:18px; margin-bottom:20px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .form-group { margin-bottom:14px; }
    .form-group.full { grid-column:1 / -1; }
    .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:#555; }
    .form-group input, .form-group select, .form-group textarea {
      width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:4px; font-size:14px;
    }
    .form-group textarea { resize:vertical; min-height:80px; }
    .btn { display:inline-block; padding:10px 20px; border:none; border-radius:4px; cursor:pointer; font-size:14px; font-weight:600; text-decoration:none; }
    .btn-primary { background:#0f0f0f; color:white; }
    .btn-primary:hover { background:#c9a227; color:#0f0f0f; }
    .btn-secondary { background:#eee; color:#333; margin-left:8px; }
    .btn-edit { background:#2980b9; color:white; padding:6px 12px; font-size:12px; border-radius:4px; margin-right:6px; }
    .btn-edit:hover { background:#1f6dad; }
    .btn-danger { background:#c0392b; color:white; padding:6px 12px; font-size:12px; border-radius:4px; }
    .btn-danger:hover { background:#a93226; }
    table { width:100%; background:white; border-collapse:collapse; border-radius:8px; overflow:hidden; }
    th, td { padding:12px 14px; text-align:left; border-bottom:1px solid #eee; font-size:14px; }
    th { background:#fafafa; font-weight:600; font-size:12px; text-transform:uppercase; }
    img.thumb { width:50px; height:60px; object-fit:cover; border-radius:4px; background:#eee; }
    .checkbox-label { display:flex; align-items:center; gap:8px; font-size:14px; }
    .edit-preview { max-width:120px; max-height:140px; object-fit:cover; border-radius:6px; margin-top:8px; background:#eee; }
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
    <h1 style="margin-bottom:24px;">Products</h1>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ========== EDIT FORM ========== -->
    <?php if ($edit_product): ?>
    <div class="card" style="border:2px solid #c9a227;">
      <h2>✏️ Edit Product #<?= (int)$edit_product['id'] ?></h2>
      <form method="POST">
        <input type="hidden" name="id" value="<?= (int)$edit_product['id'] ?>">
        <div class="form-grid">
          <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" required value="<?= htmlspecialchars($edit_product['name']) ?>">
          </div>
          <div class="form-group">
            <label>Slug</label>
            <input type="text" name="slug" value="<?= htmlspecialchars($edit_product['slug']) ?>">
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="0">— Select Category —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $edit_product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Badge</label>
            <select name="badge">
              <option value="" <?= empty($edit_product['badge']) ? 'selected' : '' ?>>— None —</option>
              <option value="Sale" <?= $edit_product['badge'] === 'Sale' ? 'selected' : '' ?>>Sale</option>
              <option value="New" <?= $edit_product['badge'] === 'New' ? 'selected' : '' ?>>New</option>
              <option value="Bestseller" <?= $edit_product['badge'] === 'Bestseller' ? 'selected' : '' ?>>Bestseller</option>
            </select>
          </div>
          <div class="form-group">
            <label>Price (৳) *</label>
            <input type="number" name="price" step="0.01" min="0" required value="<?= $edit_product['price'] ?>">
          </div>
          <div class="form-group">
            <label>Old Price (৳)</label>
            <input type="number" name="old_price" step="0.01" min="0" value="<?= $edit_product['old_price'] ?? '' ?>">
          </div>
          <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" min="0" value="<?= (int)$edit_product['stock'] ?>">
          </div>
          <div class="form-group">
            <label>Sizes (comma separated, leave empty for free size)</label>
            <input type="text" name="sizes" placeholder="S,M,L,XL" value="<?= htmlspecialchars($edit_product['sizes'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Image URL</label>
            <input type="url" name="image" value="<?= htmlspecialchars($edit_product['image'] ?? '') ?>">
            <?php if (!empty($edit_product['image'])): ?>
              <img src="<?= htmlspecialchars($edit_product['image']) ?>" class="edit-preview" alt="Preview">
            <?php endif; ?>
          </div>
          <div class="form-group full">
            <label>Description</label>
            <textarea name="description"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="status" value="1" <?= $edit_product['status'] ? 'checked' : '' ?>>
              Active (visible on store)
            </label>
          </div>
        </div>
        <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
        <a href="products.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div>
    <?php endif; ?>

    <!-- ========== ADD FORM (hide when editing) ========== -->
    <?php if (!$edit_product): ?>
    <div class="card">
      <h2>➕ Add New Product</h2>
      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="name" required placeholder="e.g. Classic Oxford Shirt">
          </div>
          <div class="form-group">
            <label>Slug (optional)</label>
            <input type="text" name="slug" placeholder="Auto-generated if empty">
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category_id">
              <option value="0">— Select Category —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Badge</label>
            <select name="badge">
              <option value="">— None —</option>
              <option value="Sale">Sale</option>
              <option value="New">New</option>
              <option value="Bestseller">Bestseller</option>
            </select>
          </div>
          <div class="form-group">
            <label>Price (৳) *</label>
            <input type="number" name="price" step="0.01" min="0" required placeholder="1890">
          </div>
          <div class="form-group">
            <label>Old Price (৳) — for sale</label>
            <input type="number" name="old_price" step="0.01" min="0" placeholder="2490">
          </div>
          <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" min="0" value="10">
          </div>
          <div class="form-group">
            <label>Sizes (comma separated, leave empty for free size)</label>
            <input type="text" name="sizes" placeholder="S,M,L,XL">
          </div>
          <div class="form-group">
            <label>Image URL</label>
            <input type="url" name="image" placeholder="https://images.unsplash.com/...">
          </div>
          <div class="form-group full">
            <label>Description</label>
            <textarea name="description" placeholder="Product description..."></textarea>
          </div>
          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" name="status" value="1" checked> Active (visible on store)
            </label>
          </div>
        </div>
        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
      </form>
    </div>
    <?php endif; ?>

    <!-- ========== PRODUCT LIST ========== -->
    <div class="card">
      <h2>All Products (<?= count($products) ?>)</h2>
      <table>
        <thead>
          <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Badge</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="8" style="text-align:center;padding:30px;color:#888;">No products yet</td></tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <img class="thumb" src="<?= htmlspecialchars($p['image'] ?: 'https://via.placeholder.com/50x60') ?>" alt="">
                </td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                <td>৳<?= number_format($p['price'], 0) ?></td>
                <td><?= $p['stock'] ?></td>
                <td><?= htmlspecialchars($p['badge'] ?? '-') ?></td>
                <td><?= $p['status'] ? 'Active' : 'Inactive' ?></td>
                <td>
                  <a href="products.php?edit=<?= $p['id'] ?>" class="btn-edit">Edit</a>
                  <a href="products.php?delete=<?= $p['id'] ?>"
                     class="btn-danger"
                     onclick="return confirm('Delete this product?')">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>