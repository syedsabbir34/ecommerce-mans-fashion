<?php
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php';
require_once '../includes/chat_knowledge.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: index.php'); exit; }
chatEnsureTables($pdo);

$msg = ''; $testResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk()) { $msg = 'Session expired. Please try again.'; }
    elseif (isset($_POST['save_notes'])) {
        $pdo->prepare("INSERT INTO site_notes (id, content) VALUES (1, ?) ON DUPLICATE KEY UPDATE content = VALUES(content)")
            ->execute([mb_substr(trim($_POST['notes'] ?? ''), 0, 6000)]);
        $msg = 'Saved. The assistant knows this from the next message.';
    } elseif (isset($_POST['test_ai'])) {
        [$tr, $tc, $te] = aiHttp(aiEndpoint(), ['model' => aiModel(AI_MODEL === 'auto'), 'max_tokens' => 400, 'messages' => [['role' => 'user', 'content' => 'Say OK']]]);
        if ($tr && $tc >= 200 && $tc < 300) {
            $testResult = ['ok' => true, 'text' => 'Connected using model ' . aiModel() . '. The AI replied: ' . mb_substr((string)(json_decode($tr, true)['choices'][0]['message']['content'] ?? '(empty)'), 0, 80)];
        } else {
            $testResult = ['ok' => false, 'text' => 'HTTP ' . $tc . ($te ? ' | ' . $te : '') . ' | ' . mb_substr(trim((string)$tr), 0, 400)];
        }
    } elseif (isset($_POST['clear_logs'])) {
        $pdo->exec("DELETE FROM chat_logs");
        $msg = 'Question list cleared.';
    }
}
$notes = chatNotes($pdo);
$logs = $pdo->query("SELECT * FROM chat_logs ORDER BY id DESC LIMIT 50")->fetchAll();
$aiOn = AI_PROVIDER !== '' && AI_API_KEY !== '';
$knowledge = chatKnowledge($pdo, '');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Chat assistant - Admin</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI',sans-serif; background:#f5f5f5; color:#333; }
    .sidebar { position:fixed; left:0; top:0; bottom:0; width:220px; background:#0f0f0f; color:white; padding:30px 20px; }
    .sidebar h2 { font-size:18px; margin-bottom:30px; }
    .sidebar a { display:block; color:#ccc; padding:10px 0; text-decoration:none; font-size:14px; }
    .sidebar a:hover { color:#c9a227; }
    .main { margin-left:220px; padding:30px; max-width:980px; }
    .alert { background:#d4edda; color:#155724; padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:14px; }
    .card { background:#fff; border-radius:8px; padding:22px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,.05); }
    h2 { font-size:18px; margin-bottom:6px; }
    p.hint { color:#777; font-size:13.5px; margin-bottom:12px; }
    textarea { width:100%; min-height:170px; padding:12px; border:1px solid #ddd; border-radius:4px; font:inherit; }
    button { padding:9px 20px; border:0; border-radius:4px; background:#0f0f0f; color:#fff; font-weight:600; cursor:pointer; margin-top:10px; }
    .badge { display:inline-block; padding:3px 12px; border-radius:20px; font-size:12.5px; font-weight:700; }
    .on { background:#d4edda; color:#155724; } .off { background:#fff3cd; color:#7a5b00; }
    table { width:100%; border-collapse:collapse; font-size:13.5px; }
    td, th { padding:8px 6px; border-bottom:1px solid #eee; text-align:left; vertical-align:top; }
    pre { background:#101820; color:#d7dde3; padding:16px; border-radius:6px; font-size:12.5px; white-space:pre-wrap; max-height:420px; overflow:auto; }
    details summary { cursor:pointer; font-weight:600; }
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
    <h1 style="margin-bottom:18px">Chat assistant</h1>
    <?php if ($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>

    <div class="card">
      <h2>Status</h2>
      <?php if ($aiOn): ?>
        <span class="badge on">AI brain ON</span>
        <p class="hint" style="margin-top:8px">Understands Bangla, English and Banglish, answers any question, and reads your live data on every message.</p>
        <form method="POST"><?= csrfField() ?><button name="test_ai" value="1">Test AI connection</button></form>
        <?php if ($testResult): ?>
          <p style="margin-top:12px;padding:10px 12px;border-radius:6px;font-size:13px;word-break:break-all;background:<?= $testResult['ok'] ? '#d4edda' : '#fdecea' ?>"><?= e($testResult['text']) ?></p>
        <?php endif; ?>
      <?php else: ?>
        <span class="badge off">AI brain OFF (basic English assistant)</span>
        <p class="hint" style="margin-top:8px">Paste an API key in <b>config/settings.php</b> (AI_API_KEY) to turn on answers for any question and Bangla support.</p>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Extra notes for the assistant</h2>
      <p class="hint">Write anything customers may ask about that is not in the product list: announcements, Eid/offer dates, store address, extra policies, common questions with answers. The assistant follows these immediately.</p>
      <form method="POST">
        <?= csrfField() ?>
        <textarea name="notes" maxlength="6000" placeholder="Example: Eid offer: 10% off on all shirts until 30 June. We do not deliver to hill tracts. Store pickup is available at ..."><?= e($notes) ?></textarea>
        <button name="save_notes" value="1">Save notes</button>
      </form>
    </div>

    <div class="card">
      <h2>Questions it could not answer</h2>
      <p class="hint">Messages that were passed to your team or left unanswered in the last 60 days. Use them to improve your notes above.</p>
      <?php if (!$logs): ?><p style="color:#888">Nothing yet.</p><?php else: ?>
        <table><tr><th>When</th><th>Customer message</th><th>Why</th></tr>
        <?php foreach ($logs as $l): ?>
          <tr><td style="white-space:nowrap"><?= date('d M, g:i A', strtotime($l['created_at'])) ?></td><td><?= e($l['message']) ?></td><td><?= e(str_replace('_', ' ', $l['reason'])) ?></td></tr>
        <?php endforeach; ?></table>
        <form method="POST" onsubmit="return confirm('Clear this list?')"><?= csrfField() ?><button name="clear_logs" value="1" style="background:#c0392b">Clear list</button></form>
      <?php endif; ?>
    </div>

    <div class="card">
      <details>
        <summary>What the assistant knows right now (live)</summary>
        <p class="hint" style="margin:10px 0">Generated from your database each time a customer sends a message. Change a product, price or stock and this changes too.</p>
        <pre><?= e($knowledge) ?></pre>
      </details>
    </div>
  </div>
</body>
</html>