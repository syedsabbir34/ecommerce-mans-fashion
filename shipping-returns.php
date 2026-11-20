<?php
$page_title = 'Delivery & returns | Mans Fashion';
include 'includes/header.php';
?>
<div class="page-head"><h1>Delivery &amp; returns</h1><p>Clear rules, no surprises.</p></div>
<div class="prose">
<h2>Delivery</h2>
<div class="tbl"><table>
  <tr><th>Area</th><th>Time</th><th>Charge</th></tr>
  <tr><td>Inside <?= e(HOME_ZONE_LABEL) ?></td><td>1–2 working days</td><td><?= money(SHIPPING_INSIDE) ?></td></tr>
  <tr><td>Outside <?= e(HOME_ZONE_LABEL) ?></td><td>2–5 working days</td><td><?= money(SHIPPING_OUTSIDE) ?></td></tr>
  <tr><td>Orders over <?= money(FREE_SHIPPING_MIN) ?></td><td>As above</td><td>Free</td></tr>
</table></div>
<p>After you place an order, we call or message you to confirm it. Dispatch happens the same or next working day after confirmation.</p>
<h2>Payment</h2>
<p>Pay cash on delivery, or pay in advance with bKash or Nagad. For mobile payments, send the exact order total and enter the transaction ID at checkout.</p>
<h2>Exchange and returns</h2>
<ul>
  <li>Request an exchange or return within <b>7 days</b> of receiving your order.</li>
  <li>Items must be unworn, unwashed and have original tags.</li>
  <li>Wrong size or a defect? We exchange it. For defects or wrong items sent by us, we cover the delivery charge.</li>
  <li>For other exchanges the customer pays the return delivery charge.</li>
  <li>Accessories such as sunglasses and watches are exchanged only if faulty.</li>
</ul>
<h2>How to start an exchange</h2>
<p>Message us on <a href="<?= e(waLink('Hi, I want to exchange my order.')) ?>" style="text-decoration:underline">WhatsApp</a> or call <?= e(CONTACT_PHONE_DISPLAY) ?> with your order number.</p>
</div>
<?php include 'includes/footer.php'; ?>
