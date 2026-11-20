(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var toast = $('#toast'), tTimer;

  function showToast(msg, isError) {
    if (!toast) return;
    toast.textContent = msg;
    toast.className = 'toast show' + (isError ? ' error' : '');
    clearTimeout(tTimer);
    tTimer = setTimeout(function () { toast.className = 'toast'; }, 3200);
  }
  window.mfToast = showToast;
  if (toast && toast.dataset.flash) showToast(toast.dataset.flash, !toast.dataset.ok);

  function setCount(n) {
    var c = $('#cartCount');
    if (!c) return;
    c.textContent = n; c.hidden = !(n > 0);
  }
  window.mfSetCount = setCount;

  // Mobile menu
  var mb = $('#menuBtn'), nav = $('#navLinks');
  if (mb && nav) mb.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    mb.setAttribute('aria-expanded', open);
  });

  // Search bar
  var sb = $('#searchBtn'), bar = $('#searchBar');
  if (sb && bar) sb.addEventListener('click', function () {
    bar.hidden = !bar.hidden;
    sb.setAttribute('aria-expanded', !bar.hidden);
    if (!bar.hidden) bar.querySelector('input').focus();
  });

  // Add to cart (no page reload)
  function addToCart(id, size, qty, btn) {
    var body = new URLSearchParams({ product_id: id, size: size || '', quantity: qty || 1 });
    if (btn) { btn.disabled = true; btn.dataset.t = btn.textContent; btn.textContent = 'Adding…'; }
    return fetch('add-to-cart.php', {
      method: 'POST', headers: { 'X-Requested-With': 'fetch' }, body: body, credentials: 'same-origin'
    }).then(function (r) { return r.json(); }).then(function (d) {
      showToast(d.message, !d.ok);
      if (d.ok) setCount(d.count);
      return d;
    }).catch(function () {
      showToast('Network problem. Please try again.', true);
      return { ok: false };
    }).then(function (d) {
      if (btn) { btn.disabled = false; btn.textContent = btn.dataset.t; }
      return d;
    });
  }
  window.mfAddToCart = addToCart;

  document.addEventListener('click', function (e) {
    var b = e.target.closest('.js-add');
    if (b) { e.preventDefault(); addToCart(b.dataset.id, b.dataset.size, 1, b); }
  });

  // Product page: size picker + quantity + buy now
  var form = $('#buyForm');
  if (form) {
    var sizeInput = $('input[name="size"]', form), sizeMsg = $('#sizeMsg'), qty = $('input[name="quantity"]', form);
    var needSize = form.dataset.needsSize === '1';
    form.addEventListener('click', function (e) {
      var s = e.target.closest('.size-opt');
      if (s && !s.disabled) {
        form.querySelectorAll('.size-opt').forEach(function (x) { x.classList.remove('on'); x.setAttribute('aria-checked', 'false'); });
        s.classList.add('on'); s.setAttribute('aria-checked', 'true');
        sizeInput.value = s.dataset.size;
        if (sizeMsg) sizeMsg.textContent = 'Selected size: ' + s.dataset.size;
      }
      var q = e.target.closest('[data-step]');
      if (q) {
        var max = parseInt(qty.max, 10) || 99, v = (parseInt(qty.value, 10) || 1) + parseInt(q.dataset.step, 10);
        qty.value = Math.min(Math.max(1, v), max);
      }
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var go = e.submitter && e.submitter.dataset.go;
      if (needSize && !sizeInput.value) {
        if (sizeMsg) { sizeMsg.textContent = 'Please choose a size to continue.'; sizeMsg.classList.add('warn'); }
        showToast('Please choose a size first.', true);
        return;
      }
      addToCart(form.dataset.id, sizeInput.value, qty.value, e.submitter).then(function (d) {
        if (d.ok && go) location.href = 'checkout.php';
      });
    });
  }

  // Cart quantity steppers
  document.addEventListener('click', function (e) {
    var st = e.target.closest('.cart-step');
    if (!st) return;
    var input = st.parentNode.querySelector('input'), max = parseInt(input.max, 10) || 99;
    input.value = Math.min(Math.max(1, (parseInt(input.value, 10) || 1) + parseInt(st.dataset.step, 10)), max);
    var f = st.closest('form'); if (f) f.submit();
  });

  // Checkout: live shipping total
  var co = $('#checkoutForm');
  if (co) {
    var sub = parseFloat(co.dataset.subtotal), ship = $('#shipVal'), tot = $('#totalVal'), note = $('#freeNote');
    var inside = parseFloat(co.dataset.inside), outside = parseFloat(co.dataset.outside), free = parseFloat(co.dataset.free);
    var fmt = function (n) { return '৳' + Math.round(n).toLocaleString('en-US'); };
    var calc = function () {
      var zone = co.querySelector('input[name="zone"]:checked').value;
      var s = sub >= free ? 0 : (zone === 'outside' ? outside : inside);
      ship.textContent = s === 0 ? 'Free' : fmt(s);
      tot.textContent = fmt(sub + s);
    };
    co.querySelectorAll('input[name="zone"]').forEach(function (r) { r.addEventListener('change', calc); });
    var pm = co.querySelectorAll('input[name="payment_method"]'), trx = $('#trxBox'), pn = $('#payNote');
    var upd = function () {
      var v = co.querySelector('input[name="payment_method"]:checked').value;
      trx.hidden = v === 'cod';
      if (v !== 'cod') pn.textContent = 'Send ' + document.getElementById('totalVal').textContent + ' to ' + (v === 'bkash' ? co.dataset.bkash : co.dataset.nagad) + ' (Send Money), then enter the transaction ID below.';
    };
    pm.forEach(function (r) { r.addEventListener('change', upd); r.addEventListener('change', calc); });
    calc(); upd();
    co.addEventListener('submit', function () { var b = $('#placeBtn'); b.disabled = true; b.textContent = 'Placing your order…'; });
  }
})();
