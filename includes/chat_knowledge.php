<?php
// includes/chat_knowledge.php
// Builds the assistant's knowledge LIVE from the database on every message.
// Add / edit / delete a product, change a price or stock, post a review, edit a note in admin: the assistant knows instantly.

function chatEnsureTables(PDO $pdo): void {
    static $done = false; if ($done) return; $done = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `site_notes` (`id` TINYINT UNSIGNED NOT NULL PRIMARY KEY, `content` TEXT, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `chat_logs` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `message` VARCHAR(400) NOT NULL, `reason` VARCHAR(30) NOT NULL, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, KEY `idx_created` (`created_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $e) { error_log('chat tables: ' . $e->getMessage()); }
}

function chatLog(PDO $pdo, string $message, string $reason): void {
    try {
        chatEnsureTables($pdo);
        $pdo->prepare("INSERT INTO chat_logs (message, reason) VALUES (?, ?)")->execute([mb_substr($message, 0, 400), $reason]);
        $pdo->exec("DELETE FROM chat_logs WHERE created_at < (NOW() - INTERVAL 60 DAY)");
    } catch (Throwable $e) {}
}

function chatNotes(PDO $pdo): string {
    try {
        chatEnsureTables($pdo);
        return trim((string)$pdo->query("SELECT content FROM site_notes WHERE id = 1")->fetchColumn());
    } catch (Throwable $e) { return ''; }
}

/** Live product rows with category, review summary and flags. */
function chatProducts(PDO $pdo): array {
    $base = "SELECT p.*, c.name AS cat, c.slug AS cat_slug";
    try {
        $rows = $pdo->query("$base, rv.a AS rating, rv.n AS rcount FROM products p LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN (SELECT product_id, AVG(rating) a, COUNT(*) n FROM reviews GROUP BY product_id) rv ON rv.product_id = p.id
            WHERE p.status = 1 ORDER BY p.id DESC")->fetchAll();
    } catch (Throwable $e) {
        $rows = $pdo->query("$base FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.status = 1 ORDER BY p.id DESC")->fetchAll();
    }
    return $rows;
}

function chatProductLine(array $p): string {
    $pct = discountPercent($p);
    $stock = (int)$p['stock'];
    $st = $stock < 1 ? 'SOLD OUT' : ($stock <= 5 ? "only $stock left" : 'in stock');
    $sizes = productSizes($p);
    $bits = [
        '#' . $p['id'], $p['name'], $p['cat'] ?: 'Uncategorized',
        money($p['price']) . ($pct ? " (was " . money($p['old_price']) . ", $pct% off)" : ''),
        $st, $sizes ? 'sizes ' . implode('/', $sizes) : 'free size',
    ];
    if (!empty($p['badge'])) $bits[] = $p['badge'];
    if (!empty($p['rcount'])) $bits[] = round((float)$p['rating'], 1) . '/5 from ' . (int)$p['rcount'] . ' reviews';
    if (strtotime($p['created_at']) > time() - 14 * 86400) $bits[] = 'NEW this fortnight';
    elseif (!empty($p['updated_at']) && strtotime($p['updated_at']) > time() - 3 * 86400) $bits[] = 'updated recently';
    $d = trim(preg_replace('/\s+/', ' ', (string)$p['description']));
    if ($d !== '') $bits[] = mb_substr($d, 0, 110);
    return implode(' | ', $bits);
}

/** Short summary of what changed lately — used for greeting and for "what's new?" questions. */
function chatHighlights(array $products): array {
    $new = $sale = $out = [];
    foreach ($products as $p) {
        if ((int)$p['stock'] < 1) { $out[] = $p; continue; }
        if (strtotime($p['created_at']) > time() - 14 * 86400) $new[] = $p;
        if (discountPercent($p) > 0) $sale[] = $p;
    }
    return ['new' => $new, 'sale' => $sale, 'out' => $out];
}

function chatKnowledge(PDO $pdo, string $query = ''): string {
    $products = chatProducts($pdo);
    $cats = $pdo->query("SELECT c.name, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 1) n FROM categories c WHERE c.status = 1 ORDER BY c.id")->fetchAll();
    $h = chatHighlights($products);

    $k  = "STORE\nName: " . SITE_NAME . " (online men's fashion store, Bangladesh). Tagline: " . SITE_TAGLINE . "\n";
    $k .= "Phone/WhatsApp: " . CONTACT_PHONE_DISPLAY . " (WhatsApp link available). No email support. Team hours: " . STORE_HOURS . ".\n";
    $k .= "Today: " . date('l, d F Y, g:i A') . " (Bangladesh time).\n\n";

    $k .= "DELIVERY: Inside " . HOME_ZONE_LABEL . " " . money(SHIPPING_INSIDE) . " (1-2 working days). Rest of Bangladesh " . money(SHIPPING_OUTSIDE) . " (2-5 working days). FREE delivery when subtotal is " . money(FREE_SHIPPING_MIN) . " or more. Orders are confirmed by phone/message, dispatched same or next working day.\n";
    $k .= "PAYMENT: Cash on delivery (no advance needed), bKash, Nagad (send money to " . BKASH_NUMBER . ", enter transaction ID at checkout). No card payment.\n";
    $k .= "EXCHANGE/RETURN: within 7 days of delivery, item unworn/unwashed with tags. Wrong size or defect: exchanged. Defect or wrong item sent by us: we pay delivery; otherwise customer pays return delivery. Watches/sunglasses exchanged only if faulty. Start via WhatsApp/phone with order number.\n";
    $k .= "CANCEL/CHANGE ORDER: possible before it ships, via WhatsApp/phone with order number.\n";
    $k .= "HOW TO ORDER: choose product and size, Add to cart or Buy now, enter name, Bangladesh mobile number, address, pick delivery area and payment, place order. No account needed. Customers can write reviews on product pages. Order tracking page needs order number + phone.\n";
    $k .= "SIZE CHARTS (inches, body): Shirts/jackets chest S 36-38, M 38-40, L 40-42, XL 42-44, XXL 44-46. Pants waist 30,32,34,36,38 (hip 37-45). Between sizes: take larger for relaxed fit. Rough guide by weight: <60kg S/30, 60-70 M/32, 70-80 L/34, 80-90 XL/36, 90+ XXL/38 (taller than 5'11 go one up).\n\n";

    $k .= "CATEGORIES (live counts): ";
    $k .= implode('; ', array_map(fn($c) => $c['name'] . ' (' . (int)$c['n'] . ' items)', $cats)) . "\n";
    $k .= "RIGHT NOW: " . count($products) . " products listed; " . count($h['sale']) . " on sale; " . count($h['new']) . " new in last 14 days; " . count($h['out']) . " sold out.\n";
    if ($h['new']) $k .= "New arrivals: " . implode(', ', array_map(fn($p) => $p['name'], array_slice($h['new'], 0, 8))) . "\n";
    if ($h['out']) $k .= "Sold out: " . implode(', ', array_map(fn($p) => $p['name'], array_slice($h['out'], 0, 8))) . "\n";

    // choose which products to list in detail (keep prompt small for big catalogs)
    $list = $products;
    if (count($products) > 40) {
        $q = mb_strtolower($query);
        $scored = [];
        foreach ($products as $p) {
            $s = 0; $hay = mb_strtolower($p['name'] . ' ' . $p['cat'] . ' ' . $p['description']);
            foreach (preg_split('/\s+/', $q) as $t) if (mb_strlen($t) >= 3 && mb_strpos($hay, $t) !== false) $s += 3;
            if (discountPercent($p) > 0) $s += 1;
            if ((int)$p['stock'] > 0) $s += 1;
            $scored[] = [$s, $p];
        }
        usort($scored, fn($a, $b) => $b[0] <=> $a[0]);
        $list = array_map(fn($x) => $x[1], array_slice($scored, 0, 40));
        $k .= "(Catalog has " . count($products) . " products; the 40 most relevant to this message are listed below.)\n";
    }
    $k .= "\nPRODUCTS (live from database; id | name | category | price | stock | sizes | notes):\n";
    foreach ($list as $p) $k .= chatProductLine($p) . "\n";

    $notes = chatNotes($pdo);
    if ($notes !== '') $k .= "\nSTORE OWNER NOTES (latest announcements, extra policies, FAQs — follow these, they override anything above):\n" . $notes . "\n";
    return $k;
}


/** One HTTP call to the AI provider. Returns [body, httpCode, curlError]. */
function aiHttp(string $url, ?array $payload = null): array {
    if (!function_exists('curl_init')) return [false, 0, 'PHP curl extension is disabled (enable extension=curl in php.ini)'];
    $local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . AI_API_KEY, 'Content-Type: application/json'],
        // XAMPP on your own computer often has no CA certificates; relax ONLY for localhost
        CURLOPT_SSL_VERIFYPEER => !$local,
        CURLOPT_SSL_VERIFYHOST => $local ? 0 : 2,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
    $res = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$res, $code, $err];
}

function aiEndpoint(): string {
    $endpoints = [
        'openai' => 'https://api.openai.com/v1/chat/completions',
        'groq'   => 'https://api.groq.com/openai/v1/chat/completions',
        'gemini' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
        'xai'    => 'https://api.x.ai/v1/chat/completions',
    ];
    return $endpoints[AI_PROVIDER] ?? $endpoints['groq'];
}


/** Model ids the provider currently offers (chat-capable only). */
function aiListModels(): array {
    [$res, $code] = aiHttp(str_replace('/chat/completions', '/models', aiEndpoint()));
    if (!$res || $code < 200 || $code >= 300) return [];
    $ids = [];
    foreach ((json_decode($res, true)['data'] ?? []) as $m) {
        $id = (string)($m['id'] ?? '');
        $id = preg_replace('#^models/#', '', $id);
        if ($id === '' || preg_match('/whisper|tts|speech|guard|embed|moderation|compound|orpheus|playai|image|vision-preview|audio|realtime|transcribe|dall|sora|safeguard/i', $id)) continue;
        $ids[] = $id;
    }
    return $ids;
}

/** The model to use. AI_MODEL = 'auto' picks a working one from the provider's live list and remembers it. */
function aiModel(bool $refresh = false): string {
    if (AI_MODEL !== 'auto' && AI_MODEL !== '' && !$refresh) return AI_MODEL;
    $cache = sys_get_temp_dir() . '/mf_ai_model_' . md5(AI_PROVIDER . AI_API_KEY) . '.txt';
    if (!$refresh && is_file($cache) && filemtime($cache) > time() - 6 * 3600) {
        $c = trim((string)@file_get_contents($cache)); if ($c !== '') return $c;
    }
    $ids = aiListModels();
    $prefs = [
        'groq'   => ['gpt-oss-120b', 'gpt-oss-20b', 'llama-3.3-70b', 'llama-3.1-8b', 'llama-4', 'qwen', 'llama'],
        'openai' => ['gpt-4o-mini', 'gpt-4.1-mini', 'gpt-5-mini', 'gpt-4o'],
        'gemini' => ['flash-latest', 'flash', 'gemma'],
        'xai'    => ['grok-3-mini', 'grok-4-fast', 'grok-4', 'grok'],
    ][AI_PROVIDER] ?? [];
    $pick = '';
    foreach ($prefs as $p) { foreach ($ids as $id) if (stripos($id, $p) !== false && stripos($id, 'preview') === false) { $pick = $id; break 2; } }
    if ($pick === '' && $ids) $pick = $ids[0];
    if ($pick === '') $pick = AI_PROVIDER === 'openai' ? 'gpt-4o-mini' : (AI_PROVIDER === 'gemini' ? 'gemini-flash-latest' : 'openai/gpt-oss-20b');
    @file_put_contents($cache, $pick);
    return $pick;
}