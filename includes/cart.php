<?php
// includes/cart.php  — cart keys look like "12|M" (product id | size)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cartKey(int $id, string $size = ''): string { return $id . '|' . $size; }
function cartParseKey(string $key): array {
    $p = explode('|', $key, 2);
    return [(int)$p[0], $p[1] ?? ''];
}
function addToCart(int $id, string $size, int $qty = 1): void {
    if ($qty < 1) return;
    $k = cartKey($id, $size);
    $_SESSION['cart'][$k] = ($_SESSION['cart'][$k] ?? 0) + $qty;
}
function updateCartQuantity(string $key, int $qty): void {
    if ($qty <= 0) { removeFromCart($key); return; }
    if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key] = $qty;
}
function removeFromCart(string $key): void { unset($_SESSION['cart'][$key]); }
function getCartCount(): int { return (int)array_sum($_SESSION['cart'] ?? []); }
function clearCart(): void { $_SESSION['cart'] = []; }

/** Returns cart lines with live product data, price and stock from the database. */
function getCartItems(PDO $pdo): array {
    if (empty($_SESSION['cart'])) return [];
    $ids = [];
    foreach (array_keys($_SESSION['cart']) as $k) { $ids[] = cartParseKey((string)$k)[0]; }
    $ids = array_values(array_unique($ids));
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT * FROM products WHERE id IN ($ph) AND status = 1");
    $st->execute($ids);
    $byId = [];
    foreach ($st->fetchAll() as $p) $byId[$p['id']] = $p;

    $items = [];
    foreach ($_SESSION['cart'] as $key => $qty) {
        [$pid, $size] = cartParseKey((string)$key);
        if (!isset($byId[$pid])) { unset($_SESSION['cart'][$key]); continue; }
        $p = $byId[$pid];
        $items[] = [
            'key' => (string)$key, 'product' => $p, 'size' => $size, 'quantity' => (int)$qty,
            'line_total' => $p['price'] * $qty,
        ];
    }
    return $items;
}
function cartSubtotal(array $items): float {
    $s = 0.0; foreach ($items as $i) $s += $i['line_total']; return $s;
}
