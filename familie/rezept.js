(function () {
  'use strict';
  var list = document.getElementById('ingList'); if (!list) return;
  var base = +list.dataset.base || 1;
  var inp = document.getElementById('persons');
  var items = [].map.call(list.querySelectorAll('li'), function (li) {
    return { li: li, amt: li.dataset.amt === '' ? null : parseFloat(li.dataset.amt), unit: li.dataset.unit || '', name: li.querySelector('.n').textContent };
  });

  var FR = [[0, ''], [1 / 8, '⅛'], [1 / 4, '¼'], [1 / 3, '⅓'], [1 / 2, '½'], [2 / 3, '⅔'], [3 / 4, '¾'], [1, '']];
  function fmt(x) {
    if (x >= 10) return String(Math.round(x * 10) / 10).replace('.', ',');
    var whole = Math.floor(x), frac = x - whole, best = null, d = 9;
    FR.forEach(function (f) { var diff = Math.abs(frac - f[0]); if (diff < d) { d = diff; best = f; } });
    if (d < 0.04) {
      if (best[0] === 1) { whole += 1; best = FR[0]; }
      return (whole ? whole : '') + (best[1] ? (whole ? ' ' : '') + best[1] : (whole ? '' : '0'));
    }
    return String(Math.round(x * 100) / 100).replace('.', ',');
  }
  function scaled(it, f) {
    if (it.amt == null) return '';
    var a = it.amt * f, u = it.unit, uu = u.toLowerCase();
    if (uu === 'g' && a >= 1000) { a /= 1000; u = 'kg'; }
    else if (uu === 'ml' && a >= 1000) { a /= 1000; u = 'l'; }
    else if (uu === 'kg' && a < 1) { a *= 1000; u = 'g'; }
    else if (uu === 'l' && a < 1) { a *= 1000; u = 'ml'; }
    if (u === 'g' || u === 'ml') a = a >= 100 ? Math.round(a / 5) * 5 : Math.round(a);
    return (u === 'g' || u === 'ml' ? String(a) : fmt(a)) + (u ? ' ' + u : '');
  }
  function persons() { var v = parseInt(inp.value, 10); return v > 0 ? Math.min(v, 200) : base; }
  function update() {
    var f = persons() / base;
    items.forEach(function (it) {
      var el = it.li.querySelector('.a');
      el.textContent = scaled(it, f);
    });
    try { history.replaceState(null, '', '#p' + persons()); } catch (e) {}
  }
  inp.addEventListener('input', update);
  document.getElementById('pMinus').onclick = function () { inp.value = Math.max(1, persons() - 1); update(); };
  document.getElementById('pPlus').onclick = function () { inp.value = Math.min(200, persons() + 1); update(); };
  document.getElementById('pReset').onclick = function () { inp.value = base; update(); };
  var m = /#p(\d+)/.exec(location.hash); if (m) inp.value = Math.min(200, Math.max(1, +m[1]));
  update();

  /* Einkaufsliste */
  var shop = document.getElementById('shopList');
  function buildShop() {
    var f = persons() / base;
    document.getElementById('shopFor').textContent = 'Menge für ' + persons() + ' Personen. Zum Abhaken antippen.';
    shop.innerHTML = '';
    items.forEach(function (it) {
      var l = document.createElement('label'); l.className = 'shop-item';
      var c = document.createElement('input'); c.type = 'checkbox';
      var s = document.createElement('span'); s.textContent = (scaled(it, f) + ' ' + it.name).trim();
      l.appendChild(c); l.appendChild(s); shop.appendChild(l);
    });
    if (!items.length) shop.innerHTML = '<p class="muted small">Keine Zutaten vorhanden.</p>';
  }
  document.addEventListener('click', function (e) { if (e.target.closest('[data-open="shopOverlay"]')) buildShop(); });
  document.getElementById('shopCopy').onclick = function () {
    var t = [].map.call(shop.querySelectorAll('span'), function (s) { return '- ' + s.textContent; }).join('\n');
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () { fam.toast('Liste kopiert.'); });
  };
  document.getElementById('shopPrint').onclick = function () {
    document.body.classList.add('print-shop'); window.print();
    setTimeout(function () { document.body.classList.remove('print-shop'); }, 500);
  };

  /* Favorit, Bewertung */
  var fav = document.getElementById('favBtn');
  fav.onclick = function () {
    fam.fetch('recipe_fav', { id: fav.dataset.id }).then(function (j) { if (j.ok) fav.textContent = j.fav ? '★ Favorit' : '☆ Merken'; else fam.toast(j.error, true); });
  };
  var stars = document.getElementById('myStars');
  stars.addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    var s = +b.dataset.s, cur = stars.querySelectorAll('.on').length;
    if (s === cur) s = 0;
    fam.fetch('recipe_rate', { id: stars.dataset.id, stars: s }).then(function (j) {
      if (!j.ok) return fam.toast(j.error, true);
      [].forEach.call(stars.querySelectorAll('button'), function (x) { x.classList.toggle('on', +x.dataset.s <= j.mine); });
      document.getElementById('avgTxt').textContent = j.count ? 'Ø ' + String(j.avg).replace('.', ',') + ' (' + j.count + ')' : 'noch keine Bewertung';
    });
  });
})();
