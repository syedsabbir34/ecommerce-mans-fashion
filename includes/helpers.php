<?php
// includes/helpers.php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return '৳' . number_format((float)$n, 0); }
function productImage(?string $img, int $w = 800): string {
    return !empty($img) ? $img : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=' . $w;
}
function productSizes(array $p): array {
    $s = trim((string)($p['sizes'] ?? ''));
    return $s === '' ? [] : array_values(array_filter(array_map('trim', explode(',', $s))));
}
function discountPercent(array $p): int {
    if (empty($p['old_price']) || $p['old_price'] <= $p['price']) return 0;
    return (int)round((1 - $p['price'] / $p['old_price']) * 100);
}
function waLink(string $text = ''): string {
    return 'https://wa.me/' . WHATSAPP_NUMBER . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}
function shippingCost(float $subtotal, string $zone): float {
    if ($subtotal <= 0 || $subtotal >= FREE_SHIPPING_MIN) return 0.0;
    return $zone === 'outside' ? (float)SHIPPING_OUTSIDE : (float)SHIPPING_INSIDE;
}
function normalizePhone(string $p): ?string {
    $p = preg_replace('/[\s\-()]/', '', $p);
    $p = preg_replace('/^(\+?88)/', '', $p);
    return preg_match('/^01[3-9]\d{8}$/', $p) ? $p : null;
}
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrfField(): string { return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">'; }
function csrfOk(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

/** Renders one product card. $redirect not needed: add-to-cart runs with JavaScript. */
function renderProductCard(array $p): void {
    $sizes = productSizes($p);
    $pct = discountPercent($p);
    $badge = $p['badge'] ?? null;
    $soldOut = (int)$p['stock'] < 1;
    ?>
    <article class="pcard">
      <a class="pcard-img" href="product.php?id=<?= (int)$p['id'] ?>">
        <img src="<?= e(productImage($p['image'], 600)) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <?php if ($soldOut): ?><span class="tag tag-out">Sold out</span>
        <?php elseif ($pct > 0): ?><span class="tag tag-sale">-<?= $pct ?>%</span>
        <?php elseif ($badge): ?><span class="tag"><?= e($badge) ?></span><?php endif; ?>
      </a>
      <div class="pcard-body">
        <?php if (!empty($p['category_name'])): ?><div class="pcard-cat"><?= e($p['category_name']) ?></div><?php endif; ?>
        <h3><a href="product.php?id=<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a></h3>
        <div class="price"><?= money($p['price']) ?>
          <?php if (!empty($p['old_price'])): ?><s><?= money($p['old_price']) ?></s><?php endif; ?></div>
        <?php if ($soldOut): ?>
          <span class="pcard-btn is-disabled">Sold out</span>
        <?php elseif ($sizes): ?>
          <a class="pcard-btn" href="product.php?id=<?= (int)$p['id'] ?>">Choose size</a>
        <?php else: ?>
          <button class="pcard-btn js-add" data-id="<?= (int)$p['id'] ?>" data-size="" type="button">Add to cart</button>
        <?php endif; ?>
      </div>
    </article>
    <?php
}

function icon(string $name, int $s = 24): string {
    $p = [
        'truck' => '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/>',
        'cash'  => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
        'swap'  => '<path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'chat'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'shield'=> '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'wa'    => '<path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.7-5.2A8.5 8.5 0 1 1 21 11.5z"/>',
    ];
    return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}
