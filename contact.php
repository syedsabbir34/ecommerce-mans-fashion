<?php
$page_title = 'Contact | Mans Fashion';
include 'includes/header.php';
?>
<div class="page-head"><h1>Contact us</h1><p>We reply fast on WhatsApp and phone.</p></div>
<div class="prose">
<div class="contact-cards">
  <a href="tel:<?= e(CONTACT_PHONE) ?>"><b>Call us</b><span><?= e(CONTACT_PHONE_DISPLAY) ?></span></a>
  <a href="<?= e(waLink('Hi Mans Fashion, I need some help.')) ?>" target="_blank" rel="noopener"><b>WhatsApp</b><span>Fastest way to reach us</span></a>
  <a href="track-order.php"><b>Track an order</b><span>Use your order number</span></a>
</div>
<h2>Opening hours</h2>
<p><?= e(STORE_HOURS) ?>. Messages sent outside these hours are answered the next morning.</p>
<h2>What we can help with</h2>
<ul>
  <li>Choosing the right size or fit</li>
  <li>Stock and delivery questions</li>
  <li>Changing or cancelling an order before it ships</li>
  <li>Exchanges and returns</li>
</ul>
<p>Our chat assistant (bottom right of every page) can answer most of these instantly, and connects you to us when it cannot.</p>
</div>
<?php include 'includes/footer.php'; ?>
