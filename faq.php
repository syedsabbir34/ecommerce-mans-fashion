<?php
$page_title = 'FAQ | Mans Fashion';
include 'includes/header.php';
?>
<div class="page-head"><h1>Frequently asked questions</h1><p>Quick answers about ordering.</p></div>
<div class="prose">
<details><summary>Do I need an account to order?</summary><p>No. Add items to your cart, enter your delivery details and place the order.</p></details>
<details><summary>Can I pay when I receive the order?</summary><p>Yes. Cash on delivery is available everywhere we deliver. You can also pay by bKash or Nagad.</p></details>
<details><summary>How long does delivery take?</summary><p>1–2 working days inside <?= e(HOME_ZONE_LABEL) ?> and 2–5 working days in the rest of Bangladesh.</p></details>
<details><summary>How much is delivery?</summary><p><?= money(SHIPPING_INSIDE) ?> inside <?= e(HOME_ZONE_LABEL) ?> and <?= money(SHIPPING_OUTSIDE) ?> elsewhere. It is free when your order is <?= money(FREE_SHIPPING_MIN) ?> or more.</p></details>
<details><summary>What if the size does not fit?</summary><p>Exchange it within 7 days if it is unworn and has its tags. See <a href="shipping-returns.php" style="text-decoration:underline">delivery &amp; returns</a>.</p></details>
<details><summary>Can I cancel or change my order?</summary><p>Yes, before it ships. Call or WhatsApp us on <?= e(CONTACT_PHONE_DISPLAY) ?> with your order number.</p></details>
<details><summary>How do I know my order was received?</summary><p>You see an order number right after checkout, and we call or message you to confirm. You can also <a href="track-order.php" style="text-decoration:underline">track your order</a> any time.</p></details>
<details><summary>How do I pay with bKash or Nagad?</summary><p>Choose bKash or Nagad at checkout. We show the number to send money to and the exact amount. After sending, type the transaction ID and place your order.</p></details>
</div>
<?php include 'includes/footer.php'; ?>
