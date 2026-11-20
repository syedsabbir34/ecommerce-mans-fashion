<?php
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) { $msg = 'Session expired. Please try again.'; }
    else {
        $rid = (int)($_POST['id'] ?? 0);
        if (isset($_POST['delete'])) {
            $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$rid]);
            $msg = 'Review deleted.';
        } elseif (isset($_POST['reply'])) {
            $text = trim($_POST['reply_text'] ?? '');
            if ($text === '') {
                $pdo->prepare("UPDATE reviews SET admin_reply = NULL, replied_at = NULL WHERE id = ?")->execute([$rid]);
                $msg = 'Reply removed.';
            } else {
                $pdo->prepare("UPDATE reviews SET admin_reply = ?, replied_at = NOW() WHERE id = ?")->execute([mb_substr($text, 0, 1000), $rid]);
                $msg = 'Reply saved. It now shows under the review.';
            }
        } elseif (isset($_POST['delete_reply'])) {
            $pdo->prepare("UPDATE reviews SET admin_reply = NULL, replied_at = NULL WHERE id = ?")->execute([$rid]);
            $msg = 'Reply removed.';
        }
    }
}

$filter = $_GET['filter'] ?? '';
$sql = "SELECT r.*, p.name AS product_name FROM reviews r LEFT JOIN products p ON p.id = r.product_id";
if ($filter === 'unanswered') $sql .= " WHERE r.admin_reply IS NULL";
$sql .= " ORDER BY r.id DESC";
$reviews = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Reviews - Admin</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',sans-serif; background:#f5f5f5; color:#333; }
    .sidebar { position:fixed; left:0; top:0; bottom:0; width:220px; background:#0f0f0f; color:white; padding:30px 20px; }
    .sidebar h2 { font-size:18px; margin-bottom:30px; }
    .sidebar a { display:block; color:#ccc; padding:10px 0; text-decoration:none; font-size:14px; }
    .sidebar a:hover { color:#c9a227; }
    .main { margin-left:220px; padding:30px; max-width:980px; }
    .alert { background:#d4edda; color:#155724; padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:14px; }
    .tabs a { display:inline-block; padding:7px 16px; border-radius:20px; background:#fff; color:#333; text-decoration:none; font-size:13px; margin-right:8px; }
    .tabs a.on { background:#0f0f0f; color:#fff; }
    .card { background:#fff; border-radius:8px; padding:20px; margin-top:16px; box-shadow:0 2px 8px rgba(0,0,0,.05); }
    .top { display:flex; gap:12px; align-items:baseline; flex-wrap:wrap; margin-bottom:6px; }
    .stars { color:#c9a227; letter-spacing:2px; }
    .meta { color:#888; font-size:12.5px; margin-left:auto; }
    .prod { font-size:13px; color:#666; margin-bottom:8px; }
    .text { margin-bottom:12px; white-space:pre-wrap; overflow-wrap:anywhere; }
    .reply { background:#f6f7f9; border-left:3px solid #c9a227; padding:10px 14px; margin-bottom:12px; font-size:14px; white-space:pre-wrap; }
    textarea { width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; font:inherit; min-height:70px; }
    .acts { display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }
    button { padding:8px 16px; border:0; border-radius:4px; cursor:pointer; font-weight:600; font-size:13px; }
    .b-dark { background:#0f0f0f; color:#fff; } .b-red { background:#fff; color:#c0392b; border:1px solid #c0392b; } .b-grey { background:#eee; }
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
    <h1 style="margin-bottom:18px">Customer reviews</h1>
    <?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
    <div class="tabs">
      <a href="reviews.php" class="<?= $filter === '' ? 'on' : '' ?>">All</a>
      <a href="reviews.php?filter=unanswered" class="<?= $filter === 'unanswered' ? 'on' : '' ?>">Not replied yet</a>
    </div>

    <?php if (!$reviews): ?><div class="card" style="text-align:center;color:#888">No reviews here yet.</div><?php endif; ?>

    <?php foreach ($reviews as $r): ?>
      <div class="card">
        <div class="top">
          <b><?= e($r['name']) ?></b>
          <span class="stars"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span>
          <span class="meta"><?= date('d M Y, g:i A', strtotime($r['created_at'])) ?></span>
        </div>
        <div class="prod">On: <a href="../product.php?id=<?= (int)$r['product_id'] ?>#reviews" target="_blank"><?= e($r['product_name'] ?? 'Deleted product') ?></a></div>
        <div class="text"><?= e($r['comment']) ?></div>

        <?php if (!empty($r['admin_reply'])): ?>
          <div class="reply"><b>Your reply:</b><br><?= e($r['admin_reply']) ?></div>
        <?php endif; ?>

        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <textarea name="reply_text" placeholder="Write a reply (shown publicly under the review)…"><?= e($r['admin_reply'] ?? '') ?></textarea>
          <div class="acts">
            <button class="b-dark" name="reply" value="1"><?= empty($r['admin_reply']) ? 'Post reply' : 'Update reply' ?></button>
            <?php if (!empty($r['admin_reply'])): ?><button class="b-grey" name="delete_reply" value="1" onclick="return confirm('Remove your reply?')">Delete reply</button><?php endif; ?>
            <button class="b-red" name="delete" value="1" onclick="return confirm('Delete this review permanently?')">Delete review</button>
          </div>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
