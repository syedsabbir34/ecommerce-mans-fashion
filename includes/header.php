<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/helpers.php';

$cart_count = getCartCount();
$page_title = $page_title ?? (SITE_NAME . ' | Premium Men\'s Fashion in Bangladesh');
$page_desc  = $page_desc  ?? 'Shirts, pants, jackets and accessories for men. Cash on delivery, 7-day exchange and nationwide delivery.';
$flashOk = $_SESSION['flash_ok'] ?? null; $flash = $flashOk ?? ($_SESSION['flash'] ?? null); unset($_SESSION['flash'], $_SESSION['flash_ok']);
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?></title>
  <meta name="description" content="<?= e($page_desc) ?>">
  <meta name="theme-color" content="#101820">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="header">
  <div class="top-bar">
    <span>Free delivery on orders over <?= money(FREE_SHIPPING_MIN) ?></span>
    <a href="tel:<?= e(CONTACT_PHONE) ?>">Call us: <?= e(CONTACT_PHONE_DISPLAY) ?></a>
  </div>
  <div class="nav-container">
    <button class="icon-btn menu-btn" aria-label="Open menu" aria-expanded="false" id="menuBtn">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <a href="index.php" class="logo" aria-label="<?= e(SITE_NAME) ?> home">MANS<span>FASHION</span></a>

    <nav class="nav-links" id="navLinks" aria-label="Main">
      <a href="index.php" class="<?= $current === 'index.php' ? 'on' : '' ?>">Home</a>
      <a href="products.php" class="<?= $current === 'products.php' && !isset($_GET['sort']) && !isset($_GET['category']) ? 'on' : '' ?>">Shop</a>
      <a href="products.php?category=shirts">Shirts</a>
      <a href="products.php?category=pants">Pants</a>
      <a href="products.php?category=jackets">Jackets</a>
      <a href="products.php?sort=new">New</a>
      <a href="products.php?sort=sale" class="sale-link">Sale</a>
    </nav>

    <div class="nav-icons">
      <button class="icon-btn" id="searchBtn" aria-label="Search" aria-expanded="false">
        <svg width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
      <a href="track-order.php" class="icon-btn hide-sm" aria-label="Track order" title="Track order">
        <svg width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 3h15v13H1zM16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/></svg>
      </a>
      <a href="cart.php" class="icon-btn" aria-label="Cart, <?= $cart_count ?> items">
        <svg width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <span class="cart-count" id="cartCount" <?= $cart_count > 0 ? '' : 'hidden' ?>><?= $cart_count ?></span>
      </a>
    </div>
  </div>
  <form class="search-bar" id="searchBar" action="products.php" method="GET" role="search" hidden>
    <input type="search" name="q" placeholder="Search shirts, jeans, jackets, watches…" aria-label="Search products" value="<?= e($_GET['q'] ?? '') ?>">
    <button type="submit">Search</button>
  </form>
</header>
<div class="toast" id="toast" role="status" aria-live="polite"<?= $flash ? ' data-flash="' . e($flash) . '"' . ($flashOk ? ' data-ok="1"' : '') : '' ?>></div>
<main id="main">
