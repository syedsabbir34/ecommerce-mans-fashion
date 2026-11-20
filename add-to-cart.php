<?php
require_once 'config/database.php';
require_once 'includes/cart.php';
require_once 'includes/helpers.php';

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
$out = ['ok' => false, 'message' => 'Could not add this item.', 'count' => getCartCount()];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['product_id'] ?? 0);
    $qty  = max(1, (int)($_POST['quantity'] ?? 1));
    $size = trim($_POST['size'] ?? '');

    $st = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $st->execute([$id]);
    $p = $st->fetch();

    if (!$p) {
        $out['message'] = 'This product is no longer available.';
    } else {
        $sizes = productSizes($p);
        $inCart = 0;
        foreach ($_SESSION['cart'] as $k => $q) { if (cartParseKey((string)$k)[0] === $id) $inCart += $q; }

        if ($sizes && !in_array($size, $sizes, true)) {
            $out['message'] = 'Please select a size first.';
        } elseif ($p['stock'] < 1) {
            $out['message'] = 'Sorry, this item is sold out.';
        } elseif ($inCart + $qty > $p['stock']) {
            $out['message'] = 'Only ' . (int)$p['stock'] . ' in stock' . ($inCart ? " (you already have $inCart in your cart)." : '.');
        } else {
            if (!$sizes) $size = '';
            addToCart($id, $size, $qty);
            $out = ['ok' => true, 'message' => $p['name'] . ($size ? " ($size)" : '') . ' added to your cart.', 'count' => getCartCount()];
        }
    }
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}
$_SESSION['flash'] = $out['message'];
$back = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
header('Location: ' . ($out['ok'] ? 'cart.php' : $back));
exit;
