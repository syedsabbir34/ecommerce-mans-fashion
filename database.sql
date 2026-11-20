-- =====================================================
-- Mans Fashion - Database (fresh install)
-- NOTE: If you already imported the old database, you do NOT need this file.
-- The website upgrades your existing database automatically.
-- =====================================================
CREATE DATABASE IF NOT EXISTS `mans_fashion` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mans_fashion`;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL UNIQUE,
  `product_count` INT UNSIGNED DEFAULT 0,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL UNIQUE,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `old_price` DECIMAL(10,2) DEFAULT NULL,
  `stock` INT UNSIGNED DEFAULT 0,
  `sizes` VARCHAR(120) DEFAULT NULL,
  `image` VARCHAR(500) DEFAULT NULL,
  `badge` VARCHAR(50) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(150) DEFAULT NULL,
  `customer_address` TEXT NOT NULL,
  `customer_city` VARCHAR(100) DEFAULT NULL,
  `shipping_zone` VARCHAR(20) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `shipping` DECIMAL(10,2) DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'cod',
  `payment_trx` VARCHAR(60) DEFAULT NULL,
  `status` ENUM('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `size` VARCHAR(20) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `line_total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `reviews` (
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
) ENGINE=InnoDB;

INSERT INTO `categories` (`name`, `slug`, `product_count`, `status`) VALUES
('Shirts','shirts',4,1),('Pants','pants',3,1),('Jackets','jackets',2,1),('Accessories','accessories',3,1);

INSERT INTO `products` (`category_id`,`name`,`slug`,`description`,`price`,`old_price`,`stock`,`sizes`,`image`,`badge`,`status`) VALUES
(1,'Classic Oxford Shirt','classic-oxford-shirt','Premium cotton Oxford shirt. Perfect for everyday office and casual wear. Soft fabric with a stylish cut.',1890,2490,45,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1596755094514-f87e34085b2c?q=80&w=800','Sale',1),
(1,'Slim Fit Formal Shirt','slim-fit-formal-shirt','Slim fit formal shirt. Ideal for office and formal events. High quality fabric.',1650,NULL,60,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?q=80&w=800','New',1),
(1,'Linen Summer Shirt','linen-summer-shirt','Lightweight linen shirt. Comfortable and stylish for hot summer days.',2100,NULL,30,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1598033129183-c4f50c736f10?q=80&w=800',NULL,1),
(1,'Checked Casual Shirt','checked-casual-shirt','Casual checked shirt. Perfect for weekend outfits.',1450,1790,40,'S,M,L,XL,XXL','https://images.unsplash.com/photo-1603252109303-2751441dd157?q=80&w=800','Sale',1),
(2,'Premium Chino Pants','premium-chino-pants','Premium chino pants. Stretch fabric with a comfortable fit.',2200,NULL,35,'30,32,34,36,38','https://images.unsplash.com/photo-1542272604-787c3835535d?q=80&w=800','Bestseller',1),
(2,'Slim Fit Jeans','slim-fit-jeans','Slim fit denim jeans. Stylish and durable.',2590,2990,50,'30,32,34,36,38','https://images.unsplash.com/photo-1541099649105-f69ad21f3246?q=80&w=800','Sale',1),
(2,'Formal Trousers','formal-trousers','Formal trousers. Perfect for office and meetings.',1950,NULL,25,'30,32,34,36,38','https://images.unsplash.com/photo-1594938298603-c8148c4dae35?q=80&w=800',NULL,1),
(3,'Leather Biker Jacket','leather-biker-jacket','Genuine look leather biker jacket. Style and protection combined.',5500,6500,12,'M,L,XL,XXL','https://images.unsplash.com/photo-1551028719-00167b16eac5?q=80&w=800','Sale',1),
(3,'Wool Blend Overcoat','wool-blend-overcoat','Wool blend overcoat. Warm and elegant for winter.',4800,NULL,18,'M,L,XL,XXL','https://images.unsplash.com/photo-1539533018447-63fcce2678e3?q=80&w=800','New',1),
(4,'Classic Leather Belt','classic-leather-belt','Genuine leather belt. Timeless design.',890,NULL,80,NULL,'https://images.unsplash.com/photo-1624222247344-550fb60583fd?q=80&w=800',NULL,1),
(4,'Minimalist Watch','minimalist-watch','Minimalist design watch. Elegant and functional.',3200,3800,22,NULL,'https://images.unsplash.com/photo-1524592094714-0f0654e20314?q=80&w=800','Sale',1),
(4,'Premium Sunglasses','premium-sunglasses','Premium sunglasses with UV protection.',1750,NULL,40,NULL,'https://images.unsplash.com/photo-1511499767150-a48a237f0083?q=80&w=800','New',1);

UPDATE categories c SET product_count = (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 1);
