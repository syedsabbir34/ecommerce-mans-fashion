<?php
// config/database.php
require_once __DIR__ . '/settings.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: empty
define('DB_NAME', 'mans_fashion');

date_default_timezone_set('Asia/Dhaka');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die("Database connection failed. Please check config/database.php");
}

/* ---- Auto-upgrade: adds new columns the first time, nothing to import by hand ---- */
try { (function (PDO $pdo) {
    $has = function (string $table, string $col) use ($pdo): bool {
        $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $s->execute([DB_NAME, $table, $col]);
        return (int)$s->fetchColumn() > 0;
    };
    if ($has('products', 'sizes')) return;

    $pdo->exec("ALTER TABLE products ADD COLUMN sizes VARCHAR(120) DEFAULT NULL AFTER stock");
    if (!$has('order_items', 'size')) {
        $pdo->exec("ALTER TABLE order_items ADD COLUMN size VARCHAR(20) DEFAULT NULL AFTER product_name");
    }
    if (!$has('orders', 'shipping_zone')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_zone VARCHAR(20) DEFAULT NULL AFTER customer_city");
    }
    if (!$has('orders', 'payment_trx')) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_trx VARCHAR(60) DEFAULT NULL AFTER payment_method");
    }
    $pdo->exec("UPDATE products p JOIN categories c ON c.id = p.category_id SET p.sizes = 'S,M,L,XL,XXL' WHERE c.slug = 'shirts' AND p.sizes IS NULL");
    $pdo->exec("UPDATE products p JOIN categories c ON c.id = p.category_id SET p.sizes = '30,32,34,36,38' WHERE c.slug = 'pants' AND p.sizes IS NULL");
    $pdo->exec("UPDATE products p JOIN categories c ON c.id = p.category_id SET p.sizes = 'M,L,XL,XXL' WHERE c.slug = 'jackets' AND p.sizes IS NULL");
})($pdo); } catch (Throwable $e) { error_log('Mans Fashion auto-upgrade: ' . $e->getMessage()); }

/* ---- Reviews table (auto-created) ---- */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reviews` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `product_id` INT UNSIGNED NOT NULL,
      `name` VARCHAR(80) NOT NULL,
      `rating` TINYINT UNSIGNED NOT NULL,
      `comment` TEXT NOT NULL,
      `admin_reply` TEXT DEFAULT NULL,
      `replied_at` DATETIME DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_product` (`product_id`),
      CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) { error_log('Mans Fashion reviews table: ' . $e->getMessage()); }
