/* Mans Fashion chat assistant — English, no auto-popups, remembers the conversation per tab */
(function () {
  'use strict';
  var KEY = 'mf_chat_v3', API = 'api/chat.php';
  var state = load() || { open: false, ctx: {}, log: [] };
  var busy = false;

  function load() { try { return JSON.parse(sessionStorage.getItem(KEY)); } catch (e) { return null; } }
  function save() { try { sessionStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {} }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fmt(t) { return esc(t).replace(/^\s*[-*] /gm, '• ').replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/\n/g, '<br>'); }

  var css = '\
#mfc{position:fixed;right:20px;bottom:20px;z-index:9000;font-family:Manrope,system-ui,sans-serif;font-size:14px;color:#27313c}\
#mfc *{box-sizing:border-box}\
#mfc-launch{display:flex;align-items:center;gap:10px;height:54px;padding:0 20px 0 16px;border:0;border-radius:30px;background:#101820;color:#fff;font:700 14px Manrope,system-ui,sans-serif;cursor:pointer;box-shadow:0 8px 26px rgba(16,24,32,.32)}\
#mfc-launch svg{color:#b08d4a}\
#mfc-launch:hover{background:#1c2733}\
#mfc.open #mfc-launch{display:none}\
#mfc-win{display:none;flex-direction:column;width:380px;height:min(600px,calc(100vh - 40px));background:#fff;border-radius:16px;box-shadow:0 18px 60px rgba(16,24,32,.28);overflow:hidden;border:1px solid #e4e6ea}\
#mfc.open #mfc-win{display:flex}\
.mfc-head{background:#101820;color:#fff;padding:14px 14px 14px 18px;display:flex;align-items:center;gap:10px}\
.mfc-head .t{flex:1;line-height:1.25}.mfc-head b{display:block;font-size:15px}.mfc-head small{color:#aeb6bf;font-size:12px}\
.mfc-head a,.mfc-head button{width:34px;height:34px;border-radius:50%;border:0;background:rgba(255,255,255,.1);color:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none}\
.mfc-head a:hover,.mfc-head button:hover{background:rgba(255,255,255,.22)}\
#mfc-log{flex:1;overflow-y:auto;padding:16px 14px;background:#f6f7f9;scroll-behavior:smooth}\
.m{max-width:86%;margin-bottom:10px;padding:10px 14px;border-radius:14px;line-height:1.5;word-wrap:break-word}\
.m.bot{background:#fff;border:1px solid #e4e6ea;border-top-left-radius:4px}\
.m.me{background:#101820;color:#fff;margin-left:auto;border-top-right-radius:4px}\
.cards{display:flex;gap:10px;overflow-x:auto;margin:0 -14px 10px;padding:2px 14px 8px;scroll-snap-type:x mandatory}\
.pc{flex:0 0 150px;scroll-snap-align:start;background:#fff;border:1px solid #e4e6ea;border-radius:10px;overflow:hidden;display:flex;flex-direction:column}\
.pc img{width:100%;height:150px;object-fit:cover;background:#eef0f2}\
.pc div{padding:8px 10px 10px;display:flex;flex-direction:column;gap:2px;flex:1}\
.pc .n{font-weight:700;font-size:13px;line-height:1.3;color:#101820}\
.pc .p{font-weight:700;font-size:13px}.pc .p s{color:#8a929b;font-weight:400;margin-left:5px;font-size:12px}\
.pc .b{margin-top:auto;display:flex;gap:6px;padding-top:6px}\
.pc .b a,.pc .b button{flex:1;text-align:center;padding:7px 4px;border-radius:6px;font:700 12px Manrope,system-ui,sans-serif;cursor:pointer;text-decoration:none;border:1.5px solid #101820;background:#fff;color:#101820}\
.pc .b .pri{background:#101820;color:#fff}\
.acts{display:flex;flex-direction:column;gap:6px;margin:0 0 10px;max-width:86%}\
.acts a{display:block;text-align:center;padding:10px 12px;border-radius:8px;border:1.5px solid #101820;font-weight:700;color:#101820;text-decoration:none;background:#fff}\
.acts a:hover{background:#101820;color:#fff}\
.acts a.wa{border-color:#25d366;color:#0f7a3d}.acts a.wa:hover{background:#25d366;color:#fff}\
.quick{display:flex;flex-wrap:wrap;gap:7px;padding:2px 0 8px}\
.quick button{border:1.5px solid #cfd3d9;background:#fff;border-radius:20px;padding:7px 13px;font:600 13px Manrope,system-ui,sans-serif;cursor:pointer;color:#27313c}\
.quick button:hover{border-color:#101820;background:#101820;color:#fff}\
.typing{display:inline-flex;gap:4px;padding:13px 14px}.typing i{width:7px;height:7px;border-radius:50%;background:#9aa2ab;animation:mfb 1s infinite}.typing i:nth-child(2){animation-delay:.15s}.typing i:nth-child(3){animation-delay:.3s}\
@keyframes mfb{0%,60%,100%{opacity:.3;transform:none}30%{opacity:1;transform:translateY(-3px)}}\
.mfc-in{display:flex;gap:8px;padding:10px 12px;background:#fff;border-top:1px solid #e4e6ea}\
.mfc-in input{flex:1;border:1.5px solid #e4e6ea;border-radius:22px;padding:11px 16px;font:inherit;outline:0}\
.mfc-in input:focus{border-color:#b08d4a}\
.mfc-in button{width:44px;height:44px;border-radius:50%;border:0;background:#b08d4a;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center}\
.mfc-in button:disabled{opacity:.5;cursor:default}\
@media (max-width:560px){#mfc{right:12px;bottom:12px}#mfc.open{right:0;bottom:0;left:0;top:0}#mfc.open #mfc-win{width:100%;height:100%;border-radius:0;border:0}#mfc-launch span{display:none}#mfc-launch{width:54px;padding:0;justify-content:center}.mfc-in input{font-size:16px}}\
@media (prefers-reduced-motion:reduce){#mfc-log{scroll-behavior:auto}.typing i{animation:none}}';

  var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

  var root = document.createElement('div'); root.id = 'mfc';
  root.innerHTML =
    '<button id="mfc-launch" type="button" aria-label="Open chat assistant"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>Chat with us</span></button>' +
    '<div id="mfc-win" role="dialog" aria-label="Mans Fashion assistant">' +
      '<div class="mfc-head"><div class="t"><b>Mans Fashion assistant</b><small>Ask anything · Live store info</small></div>' +
        '<a href="https://wa.me/' + (window.MF ? MF.wa : '') + '" target="_blank" rel="noopener" aria-label="Chat with a person on WhatsApp" title="WhatsApp"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.7-5.2A8.5 8.5 0 1 1 21 11.5z"/></svg></a>' +
        '<a href="tel:' + (window.MF ? MF.phone : '') + '" aria-label="Call us" title="Call"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg></a>' +
        '<button type="button" id="mfc-close" aria-label="Close chat"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button></div>' +
      '<div id="mfc-log" role="log" aria-live="polite"></div>' +
      '<form class="mfc-in" id="mfc-form" autocomplete="off"><input id="mfc-input" type="text" maxlength="400" placeholder="Ask me anything…" aria-label="Your message"><button type="submit" id="mfc-send" aria-label="Send"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21 23 12 2 3v7l15 2-15 2z"/></svg></button></form>' +
    '</div>';
  document.body.appendChild(root);

  var log = root.querySelector('#mfc-log'), input = root.querySelector('#mfc-input'), send = root.querySelector('#mfc-send');

  function scroll() { log.scrollTop = log.scrollHeight; }
  function clearQuick() { log.querySelectorAll('.quick').forEach(function (q) { q.remove(); }); }

  function render(entry, animate) {
    if (entry.role === 'me') {
      var d = document.createElement('div'); d.className = 'm me'; d.textContent = entry.text; log.appendChild(d); return;
    }
    var b = document.createElement('div'); b.className = 'm bot'; b.innerHTML = fmt(entry.text); log.appendChild(b);

    if (entry.products && entry.products.length) {
      var wrap = document.createElement('div'); wrap.className = 'cards';
      entry.products.forEach(function (p) {
        var c = document.createElement('div'); c.className = 'pc';
        var btn = p.soldOut ? '<a href="' + esc(p.url) + '">View</a>'
          : (p.needsSize ? '<a class="pri" href="' + esc(p.url) + '">Choose size</a>'
            : '<a href="' + esc(p.url) + '">View</a><button type="button" class="pri" data-add="' + p.id + '">Add</button>');
        c.innerHTML = '<a href="' + esc(p.url) + '"><img src="' + esc(p.image) + '" alt="' + esc(p.name) + '" loading="lazy"></a><div><span class="n">' + esc(p.name) + '</span><span class="p">' + esc(p.price) + (p.old ? '<s>' + esc(p.old) + '</s>' : '') + '</span>' + (p.soldOut ? '<span style="color:#b3261e;font-size:12px">Sold out</span>' : '') + '<span class="b">' + btn + '</span></div>';
        wrap.appendChild(c);
      });
      log.appendChild(wrap);
    }
    if (entry.actions && entry.actions.length) {
      var a = document.createElement('div'); a.className = 'acts';
      entry.actions.forEach(function (x) {
        var l = document.createElement('a'); l.href = x.url; l.textContent = x.label;
        if (x.ext) { l.target = '_blank'; l.rel = 'noopener'; if (/wa\.me/.test(x.url)) l.className = 'wa'; }
        a.appendChild(l);
      });
      log.appendChild(a);
    }
    if (entry.quick && entry.quick.length && entry.last !== false) {
      var q = document.createElement('div'); q.className = 'quick';
      entry.quick.forEach(function (t) { var k = document.createElement('button'); k.type = 'button'; k.textContent = t; k.dataset.q = t; q.appendChild(k); });
      log.appendChild(q);
    }
  }

  function renderAll() {
    log.innerHTML = '';
    state.log.forEach(function (e, i) { e.last = i === state.log.length - 1; render(e); });
    scroll();
  }

  function greet(done) {
    var base = "Hi, I am the Mans Fashion assistant. Ask me anything: products, sizes, delivery, orders, or even style advice.";
    var finish = function (extra) {
      state.log.push({ role: 'bot', text: base + (extra ? '\n\n' + extra : ''), quick: ['Shop shirts', 'Sale items', 'What is new?', 'Track my order', 'Talk to a person'] });
      save(); done();
    };
    var t = setTimeout(function () { t = null; finish(''); }, 1500);
    fetch(API + '?init=1', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
      if (t === null) return; clearTimeout(t); finish(d.highlight || '');
    }).catch(function () { if (t !== null) { clearTimeout(t); finish(''); } });
  }

  function open() {
    root.classList.add('open'); state.open = true; save();
    if (!state.log.length) { greet(function () { renderAll(); input.focus(); }); return; }
    renderAll(); setTimeout(function () { input.focus(); }, 50);
  }
  function close() { root.classList.remove('open'); state.open = false; save(); root.querySelector('#mfc-launch').focus(); }

  function typing(on) {
    var t = log.querySelector('.typing-wrap');
    if (on && !t) { t = document.createElement('div'); t.className = 'm bot typing-wrap'; t.innerHTML = '<span class="typing"><i></i><i></i><i></i></span>'; log.appendChild(t); scroll(); }
    if (!on && t) t.remove();
  }

  function ask(text) {
    text = (text || '').trim();
    if (!text || busy) return;
    busy = true; send.disabled = true;
    clearQuick();
    state.log.push({ role: 'me', text: text }); render({ role: 'me', text: text }); scroll(); save();
    typing(true);
    var started = Date.now();
    fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ message: text, ctx: state.ctx, history: state.log.slice(-8).map(function (e) { return { role: e.role, text: e.text }; }) }) })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (d) { return d; })
      .catch(function () {
        return { reply: "Sorry, I could not connect just now. You can reach our team directly.", quick: ['Try again'], actions: [{ label: 'Chat on WhatsApp', url: 'https://wa.me/' + (window.MF ? MF.wa : ''), ext: true }], ctx: state.ctx, retry: text };
      })
      .then(function (d) {
        var wait = Math.max(0, 450 - (Date.now() - started));
        setTimeout(function () {
          typing(false);
          if (d.ctx) state.ctx = d.ctx;
          var entry = { role: 'bot', text: d.reply, products: d.products || [], actions: d.actions || [], quick: d.quick || [] };
          if (d.retry) state.retry = d.retry;
          state.log.push(entry); state.log = state.log.slice(-40);
          entry.last = true; render(entry); scroll(); save();
          busy = false; send.disabled = false; input.focus();
        }, wait);
      });
  }

  root.querySelector('#mfc-launch').addEventListener('click', open);
  root.querySelector('#mfc-close').addEventListener('click', close);
  root.querySelector('#mfc-form').addEventListener('submit', function (e) { e.preventDefault(); var v = input.value; input.value = ''; ask(v); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('open')) close(); });

  log.addEventListener('click', function (e) {
    var q = e.target.closest('.quick button');
    if (q) { ask(q.dataset.q === 'Try again' && state.retry ? state.retry : q.dataset.q); return; }
    var a = e.target.closest('[data-add]');
    if (a && window.mfAddToCart) { window.mfAddToCart(a.dataset.add, '', 1, a); }
  });

  if (state.open) open();
})();
