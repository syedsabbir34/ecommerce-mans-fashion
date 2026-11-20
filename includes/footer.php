</main>
<footer class="footer">
  <div class="footer-grid">
    <div class="footer-brand">
      <a href="index.php" class="logo">MANS<span>FASHION</span></a>
      <p>Quality menswear for work, weekends and everything in between. Order online, pay on delivery.</p>
      <a class="wa-btn" href="<?= e(waLink('Hi Mans Fashion, I need help with an order.')) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
    </div>
    <div>
      <h4>Shop</h4>
      <ul>
        <li><a href="products.php">All products</a></li>
        <li><a href="products.php?sort=new">New arrivals</a></li>
        <li><a href="products.php?sort=sale">Sale</a></li>
        <li><a href="products.php?category=shirts">Shirts</a></li>
        <li><a href="products.php?category=pants">Pants</a></li>
        <li><a href="products.php?category=jackets">Jackets</a></li>
        <li><a href="products.php?category=accessories">Accessories</a></li>
      </ul>
    </div>
    <div>
      <h4>Help</h4>
      <ul>
        <li><a href="track-order.php">Track your order</a></li>
        <li><a href="shipping-returns.php">Delivery &amp; returns</a></li>
        <li><a href="size-guide.php">Size guide</a></li>
        <li><a href="faq.php">FAQ</a></li>
        <li><a href="about.php">About us</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul class="contact-list">
        <li><a href="tel:<?= e(CONTACT_PHONE) ?>"><?= e(CONTACT_PHONE_DISPLAY) ?></a></li>
        <li><a href="<?= e(waLink()) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
        <li><?= e(STORE_HOURS) ?></li>
      </ul>
      <h4 style="margin-top:22px">We accept</h4>
      <p class="pay-list">Cash on delivery · bKash · Nagad</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
    <p>Made by <?= e(MADE_BY) ?></p>
  </div>
</footer>
<script>window.MF = { phone: <?= json_encode(CONTACT_PHONE) ?>, wa: <?= json_encode(WHATSAPP_NUMBER) ?>, freeMin: <?= (int)FREE_SHIPPING_MIN ?> };</script>
<script src="assets/js/site.js?v=3"></script>
<script src="assets/js/chatbot.js?v=3"></script>
</body>
</html>
