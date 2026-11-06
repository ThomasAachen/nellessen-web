(function () {
  'use strict';
  var D = window.TREE, vp = document.getElementById('treeVp');
  if (!D || !vp) return;
  var canvas = document.getElementById('treeCanvas'), svg = document.getElementById('treeSvg'), panel = document.getElementById('treePanel');
  var CW = 176, CH = 64, PG = 40, UG = 36, VG = 96, M = 50;
  var NS = 'http://www.w3.org/2000/svg';

  var P = {}, ids = [];
  D.persons.forEach(function (p) { P[p.id] = p; ids.push(p.id); });
  var parentsOf = {}, childrenOf = {}, partnersOf = {}, pships = [];
  ids.forEach(function (i) { parentsOf[i] = []; childrenOf[i] = []; partnersOf[i] = []; });
  D.links.forEach(function (l) { if (P[l[0]] && P[l[1]] && l[0] !== l[1]) { parentsOf[l[1]].push(l[0]); childrenOf[l[0]].push(l[1]); } });
  D.partners.forEach(function (s) { if (P[s[0]] && P[s[1]] && s[0] !== s[1]) { partnersOf[s[0]].push(s[1]); partnersOf[s[1]].push(s[0]); pships.push(s); } });

  function name(p) { return ((p.first || '') + ' ' + (p.last || '')).trim() || 'Unbekannt'; }
  function year(s) { var m = /^(\d{4})/.exec(s || ''); return m ? m[1] : ''; }
  function years(p) {
    var b = year(p.bd), d = year(p.dd);
    if (b && d) return b + ' – ' + d;
    if (b) return (p.dd ? '' : '* ') + b;
    if (d) return '† ' + d;
    return p.dd ? '†' : '';
  }
  function initials(p) { return ((p.first || '?').charAt(0) + (p.last || '').charAt(0)).toUpperCase(); }

  /* 1. Generationen (Fixpunkt: Kinder unter Eltern, Partner auf gleicher Ebene) */
  var gen = {}; ids.forEach(function (i) { gen[i] = 0; });
  var cap = ids.length + 2;
  for (var it = 0; it < cap * 2; it++) {
    var ch = false;
    D.links.forEach(function (l) { if (P[l[0]] && P[l[1]] && gen[l[1]] < gen[l[0]] + 1 && gen[l[0]] < cap) { gen[l[1]] = gen[l[0]] + 1; ch = true; } });
    pships.forEach(function (s) { var m = Math.max(gen[s[0]], gen[s[1]]); if (gen[s[0]] !== m) { gen[s[0]] = m; ch = true; } if (gen[s[1]] !== m) { gen[s[1]] = m; ch = true; } });
    if (!ch) break;
  }

  /* 2. Einheiten: Partnerketten */
  var comp = {};
  function find(x) { while (comp[x] !== x) { comp[x] = comp[comp[x]]; x = comp[x]; } return x; }
  ids.forEach(function (i) { comp[i] = i; });
  pships.forEach(function (s) { comp[find(s[0])] = find(s[1]); });
  var groups = {};
  ids.forEach(function (i) { (groups[find(i)] = groups[find(i)] || []).push(i); });
  var units = [], unitOf = {};
  Object.keys(groups).forEach(function (k) {
    var g = groups[k], order = [];
    if (g.length > 1) {
      var seen = {}, start = g.slice().sort(function (a, b) { return partnersOf[a].length - partnersOf[b].length || a - b; })[0];
      (function walk(x) { seen[x] = 1; order.push(x); partnersOf[x].slice().sort(function (a, b) { return a - b; }).forEach(function (y) { if (!seen[y]) walk(y); }); })(start);
    } else order = g;
    var u = { members: order, gen: gen[order[0]], w: order.length * CW + (order.length - 1) * PG, x: 0 };
    u.key = order.reduce(function (m, i) { var b = P[i].bd || '9999'; return b < m ? b : m; }, '9999');
    units.push(u); order.forEach(function (i) { unitOf[i] = u; });
  });
  function uniq(a) { return a.filter(function (v, i) { return a.indexOf(v) === i; }); }
  units.forEach(function (u) {
    u.up = uniq([].concat.apply([], u.members.map(function (i) { return parentsOf[i].map(function (p) { return unitOf[p]; }); }))).filter(function (x) { return x !== u; });
    u.down = uniq([].concat.apply([], u.members.map(function (i) { return childrenOf[i].map(function (c) { return unitOf[c]; }); }))).filter(function (x) { return x !== u; });
  });

  /* 3. Startreihenfolge: Tiefensuche, Geschwister nach Geburtsdatum */
  var maxGen = 0; units.forEach(function (u) { if (u.gen > maxGen) maxGen = u.gen; });
  var rowsU = []; for (var g = 0; g <= maxGen; g++) rowsU.push([]);
  var visited = [];
  function visit(u) {
    if (visited.indexOf(u) > -1) return; visited.push(u);
    rowsU[u.gen].push(u);
    u.down.slice().sort(function (a, b) { return a.key < b.key ? -1 : a.key > b.key ? 1 : 0; }).forEach(visit);
  }
  units.filter(function (u) { return !u.up.length; }).sort(function (a, b) { return a.gen - b.gen || (b.down.length - a.down.length); }).forEach(visit);
  units.forEach(visit);

  function center(u) { return u.x + u.w / 2; }
  /* gewichtete isotone Regression: Mindestabstände, minimale Abweichung vom Wunsch */
  function place(row, want, wt) {
    var n = row.length; if (!n) return;
    var s = [], acc = 0, i;
    for (i = 0; i < n; i++) { s[i] = acc; acc += row[i].w + UG; }
    var blocks = [];
    for (i = 0; i < n; i++) {
      blocks.push({ sum: (want[i] - row[i].w / 2 - s[i]) * wt[i], w: wt[i], n: 1 });
      while (blocks.length > 1) {
        var a = blocks[blocks.length - 2], b = blocks[blocks.length - 1];
        if (a.sum / a.w > b.sum / b.w) { blocks.splice(blocks.length - 2, 2, { sum: a.sum + b.sum, w: a.w + b.w, n: a.n + b.n }); } else break;
      }
    }
    var k = 0;
    blocks.forEach(function (bl) { var z = bl.sum / bl.w; for (var j = 0; j < bl.n; j++, k++) row[k].x = z + s[k]; });
  }
  function initial(row) { var x = 0; row.forEach(function (u) { u.x = x; x += u.w + UG; }); }
  rowsU.forEach(initial);

  function mean(list) { var t = 0; list.forEach(function (u) { t += center(u); }); return t / list.length; }
  function pass(dir, reorder) {
    var gs = []; for (var q = 0; q <= maxGen; q++) gs.push(q);
    if (dir < 0) gs.reverse();
    gs.forEach(function (gi) {
      var row = rowsU[gi], neigh = dir > 0 ? 'up' : 'down';
      if (dir > 0 && gi === 0) return; if (dir < 0 && gi === maxGen) return;
      var info = row.map(function (u) { var l = u[neigh]; return { u: u, want: l.length ? mean(l) : center(u), has: l.length > 0 }; });
      if (reorder) info.sort(function (a, b) { return a.want - b.want || (a.u.key < b.u.key ? -1 : 1); });
      row.length = 0; info.forEach(function (o) { row.push(o.u); });
      place(row, info.map(function (o) { return o.want; }), info.map(function (o) { return o.has ? 1 : 0.15; }));
    });
  }
  for (var r = 0; r < 4; r++) { pass(1, true); pass(-1, true); }
  for (r = 0; r < 4; r++) { pass(1, false); pass(-1, false); }
  pass(1, false);

  /* 4. Koordinaten */
  var minX = Infinity, maxX = -Infinity;
  units.forEach(function (u) { minX = Math.min(minX, u.x); maxX = Math.max(maxX, u.x + u.w); });
  var pos = {};
  units.forEach(function (u) {
    u.members.forEach(function (i, k) { pos[i] = { x: u.x - minX + M + k * (CW + PG), y: M + u.gen * (CH + VG) }; });
  });
  var W = maxX - minX + 2 * M, H = M * 2 + maxGen * (CH + VG) + CH;
  canvas.style.width = W + 'px'; canvas.style.height = H + 'px';
  svg.setAttribute('width', W); svg.setAttribute('height', H);

  /* 5. Zeichnen */
  var cards = {};
  ids.forEach(function (i) {
    var p = P[i], a = document.createElement('div');
    a.className = 'pcard ' + (p.gender === 'm' ? 'm' : p.gender === 'f' ? 'f' : '') + (p.dd ? ' dead' : '');
    a.style.cssText = 'left:' + pos[i].x + 'px;top:' + pos[i].y + 'px;width:' + CW + 'px;height:' + CH + 'px';
    a.dataset.id = i;
    var ph = document.createElement('div'); ph.className = 'ph';
    if (p.photo) { var im = document.createElement('img'); im.src = p.photo; im.alt = ''; im.loading = 'lazy'; ph.appendChild(im); } else ph.textContent = initials(p);
    var t = document.createElement('div'); t.style.minWidth = 0;
    var n = document.createElement('div'); n.className = 'nm'; n.textContent = name(p);
    var y = document.createElement('div'); y.className = 'yr'; y.textContent = years(p);
    t.appendChild(n); t.appendChild(y); a.appendChild(ph); a.appendChild(t); canvas.appendChild(a); cards[i] = a;
  });
  function mk(d, cls) { var e = document.createElementNS(NS, 'path'); e.setAttribute('d', d); if (cls) e.setAttribute('class', cls); svg.appendChild(e); }
  pships.forEach(function (s) {
    var a = pos[s[0]], b = pos[s[1]]; if (a.y !== b.y) return;
    var l = a.x < b.x ? a : b, rgt = a.x < b.x ? b : a, adjacent = Math.abs(rgt.x - (l.x + CW)) <= PG + 1;
    if (adjacent) mk('M' + (l.x + CW) + ' ' + (l.y + CH / 2) + 'H' + rgt.x, 'partner');
    else mk('M' + (l.x + CW / 2) + ' ' + (l.y + CH) + 'Q' + ((l.x + rgt.x + CW) / 2) + ' ' + (l.y + CH + 30) + ' ' + (rgt.x + CW / 2) + ' ' + (l.y + CH), 'partner');
  });
  ids.forEach(function (c) {
    var ps = parentsOf[c]; if (!ps.length) return;
    var cx = pos[c].x + CW / 2, top = pos[c].y;
    var lowest = Math.max.apply(null, ps.map(function (p) { return pos[p].y; }));
    var bus = lowest + CH + VG / 2, srcs = [], used = {};
    ps.forEach(function (p) {
      if (used[p]) return;
      var mate = ps.filter(function (q) { return q !== p && !used[q] && partnersOf[p].indexOf(q) > -1 && pos[q].y === pos[p].y && Math.abs(pos[q].x - pos[p].x) <= CW + PG + 1; })[0];
      used[p] = 1;
      if (mate) { used[mate] = 1; var l = Math.min(pos[p].x, pos[mate].x); srcs.push([l + CW + PG / 2, pos[p].y + CH / 2]); }
      else srcs.push([pos[p].x + CW / 2, pos[p].y + CH]);
    });
    var xs = srcs.map(function (s) { return s[0]; }).concat([cx]);
    srcs.forEach(function (s) { mk('M' + s[0] + ' ' + s[1] + 'V' + bus); });
    mk('M' + Math.min.apply(null, xs) + ' ' + bus + 'H' + Math.max.apply(null, xs));
    mk('M' + cx + ' ' + bus + 'V' + top);
  });

  /* 6. Zoom und Verschieben */
  var tx = 0, ty = 0, k = 1, moved = false;
  function apply() { canvas.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + k + ')'; }
  function fit() {
    var r = vp.getBoundingClientRect(), s = Math.min(r.width / W, r.height / H, 1);
    k = Math.max(0.2, s); tx = (r.width - W * k) / 2; ty = Math.max(10, (r.height - H * k) / 2); apply();
  }
  function zoomAt(f, cx, cy) {
    var nk = Math.min(2.5, Math.max(0.15, k * f)); f = nk / k;
    tx = cx - (cx - tx) * f; ty = cy - (cy - ty) * f; k = nk; apply();
  }
  function focusOn(id, zoom) {
    var p = pos[id]; if (!p) return;
    var r = vp.getBoundingClientRect(); if (zoom) k = Math.max(k, 0.9);
    tx = (r.width - (panel.classList.contains('open') ? 340 : 0)) / 2 - (p.x + CW / 2) * k; ty = r.height / 2 - (p.y + CH / 2) * k; apply();
  }
  document.getElementById('zIn').onclick = function () { var r = vp.getBoundingClientRect(); zoomAt(1.25, r.width / 2, r.height / 2); };
  document.getElementById('zOut').onclick = function () { var r = vp.getBoundingClientRect(); zoomAt(0.8, r.width / 2, r.height / 2); };
  document.getElementById('zFit').onclick = fit;
  vp.addEventListener('wheel', function (e) {
    e.preventDefault(); var r = vp.getBoundingClientRect();
    zoomAt(e.deltaY < 0 ? 1.12 : 1 / 1.12, e.clientX - r.left, e.clientY - r.top);
  }, { passive: false });
  var ptrs = {}, last = null, pinch = 0;
  vp.addEventListener('pointerdown', function (e) {
    if (e.target.closest('.tree-panel') || e.target.closest('.tree-tools')) return;
    ptrs[e.pointerId] = { x: e.clientX, y: e.clientY }; moved = false;
    last = { x: e.clientX, y: e.clientY }; pinch = 0; vp.classList.add('drag');
  });
  window.addEventListener('pointermove', function (e) {
    if (!ptrs[e.pointerId]) return;
    ptrs[e.pointerId] = { x: e.clientX, y: e.clientY };
    var keys = Object.keys(ptrs);
    if (keys.length === 2) {
      var a = ptrs[keys[0]], b = ptrs[keys[1]], d = Math.hypot(a.x - b.x, a.y - b.y), r = vp.getBoundingClientRect();
      if (pinch) zoomAt(d / pinch, (a.x + b.x) / 2 - r.left, (a.y + b.y) / 2 - r.top);
      pinch = d; moved = true; return;
    }
    var dx = e.clientX - last.x, dy = e.clientY - last.y;
    if (Math.abs(dx) + Math.abs(dy) > 3) moved = true;
    if (moved) { tx += dx; ty += dy; apply(); }
    last = { x: e.clientX, y: e.clientY };
  });
  function up(e) { delete ptrs[e.pointerId]; pinch = 0; last = { x: e.clientX, y: e.clientY }; if (!Object.keys(ptrs).length) vp.classList.remove('drag'); }
  window.addEventListener('pointerup', up); window.addEventListener('pointercancel', up);

  /* 7. Detailfenster */
  var sel = null;
  function link(id) { var a = document.createElement('a'); a.href = '#'; a.textContent = name(P[id]); a.onclick = function (e) { e.preventDefault(); show(id, true); }; return a; }
  function show(id, center) {
    var p = P[id]; if (!p) return;
    if (sel && cards[sel]) cards[sel].classList.remove('sel');
    sel = id; cards[id].classList.add('sel'); panel.innerHTML = '';
    var x = document.createElement('button'); x.className = 'lb-close'; x.style.cssText = 'position:absolute;right:.5rem;top:.5rem;left:auto;width:34px;height:34px'; x.textContent = '×'; x.onclick = hide; panel.appendChild(x);
    var big = document.createElement('div'); big.className = 'big-ph';
    if (p.photo) { var im = document.createElement('img'); im.src = p.photo.replace('&s=thumb', ''); im.alt = ''; big.appendChild(im); } else big.textContent = initials(p);
    panel.appendChild(big);
    var h = document.createElement('h3'); h.textContent = name(p); panel.appendChild(h);
    if (p.birth_name) { var bn = document.createElement('div'); bn.className = 'muted small'; bn.textContent = 'geb. ' + p.birth_name; panel.appendChild(bn); }
    var dl = document.createElement('dl');
    function row(label, val) { if (!val) return; var dt = document.createElement('dt'), dd = document.createElement('dd'); dt.textContent = label; if (typeof val === 'string') dd.textContent = val; else dd.appendChild(val); dl.appendChild(dt); dl.appendChild(dd); }
    row('Geboren', [p.bd_txt, p.bp].filter(Boolean).join(', '));
    row('Gestorben', [p.dd_txt, p.dp].filter(Boolean).join(', '));
    if (p.age !== null && p.age !== undefined) row(p.dd ? 'Alter' : 'Alter heute', p.age + ' Jahre');
    row('Konto', p.user ? 'verknüpft mit ' + p.user : '');
    function list(ids2) { var s = document.createElement('span'); ids2.forEach(function (i, n) { if (n) s.appendChild(document.createTextNode(', ')); s.appendChild(link(i)); }); return s; }
    if (parentsOf[id].length) row('Eltern', list(parentsOf[id]));
    var pt = partnersOf[id]; if (pt.length) row('Partner', list(pt));
    if (childrenOf[id].length) row('Kinder', list(childrenOf[id].slice().sort(function (a, b) { return (P[a].bd || '9') < (P[b].bd || '9') ? -1 : 1; })));
    var sib = uniq([].concat.apply([], parentsOf[id].map(function (q) { return childrenOf[q]; }))).filter(function (s) { return s !== id; });
    if (sib.length) row('Geschwister', list(sib));
    panel.appendChild(dl);
    if (p.bio) { var b = document.createElement('div'); b.className = 'bio'; b.textContent = p.bio; panel.appendChild(b); }
    var act = document.createElement('div'); act.className = 'actions'; act.style.cssText = 'display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.2rem';
    function btn(t, href) { var a = document.createElement('a'); a.className = 'btn btn-outline btn-sm'; a.href = href; a.textContent = t; act.appendChild(a); }
    var mc = D.mem[id] || 0; if (mc) btn('Geschichten (' + mc + ')', 'erinnerungen.php?person=' + id);
    if (D.canEdit) {
      btn('Bearbeiten', 'person_edit.php?id=' + id);
      btn('+ Kind', 'person_edit.php?rel=child&of=' + id);
      btn('+ Partner', 'person_edit.php?rel=partner&of=' + id);
      btn('+ Elternteil', 'person_edit.php?rel=parent&of=' + id);
    }
    panel.appendChild(act);
    panel.classList.add('open');
    if (center) focusOn(id, false);
    try { history.replaceState(null, '', 'stammbaum.php?p=' + id); } catch (e) {}
  }
  function hide() { panel.classList.remove('open'); if (sel && cards[sel]) cards[sel].classList.remove('sel'); sel = null; }
  canvas.addEventListener('click', function (e) {
    var c = e.target.closest('.pcard'); if (!c || moved) return; show(+c.dataset.id, false);
  });
  vp.addEventListener('click', function (e) { if (!moved && !e.target.closest('.pcard') && !e.target.closest('.tree-panel') && !e.target.closest('.tree-tools')) hide(); });

  /* 8. Suche */
  var inp = document.getElementById('treeSearch'), info = document.getElementById('treeInfo'), hits = [], hi = 0;
  inp.addEventListener('input', function () {
    var q = inp.value.trim().toLowerCase(); hits = []; hi = 0;
    ids.forEach(function (i) { var m = q && (name(P[i]) + ' ' + (P[i].birth_name || '')).toLowerCase().indexOf(q) > -1; cards[i].classList.toggle('hit', !!m); if (m) hits.push(i); });
    info.textContent = q ? hits.length + (hits.length === 1 ? ' Treffer' : ' Treffer') : ids.length + ' Personen';
    if (hits.length) focusOn(hits[0], true);
  });
  inp.addEventListener('keydown', function (e) { if (e.key === 'Enter' && hits.length) { e.preventDefault(); hi = (hi + 1) % hits.length; focusOn(hits[hi], true); } });
  info.textContent = ids.length + ' Personen';

  fit();
  if (D.focus && P[D.focus]) { show(D.focus, true); }
  window.addEventListener('resize', function () { if (!sel) fit(); });
})();
