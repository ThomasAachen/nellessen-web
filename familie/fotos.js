(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
  var grid = $('#grid');
  var ids = grid ? [].map.call(grid.querySelectorAll('.thumb'), function (a) { return +a.dataset.id; }) : [];

  /* ── Auswahlmodus ── */
  var selecting = false;
  var selBtn = $('#selToggle');
  function picked() { return grid ? [].filter.call(grid.querySelectorAll('.thumb.picked'), Boolean).map(function (a) { return +a.dataset.id; }) : []; }
  function updBulk() { $('#bulkN').textContent = picked().length; }
  if (selBtn) selBtn.addEventListener('click', function () {
    selecting = !selecting;
    if (grid) grid.classList.toggle('selecting', selecting);
    $('#bulkBar').style.display = selecting ? 'block' : 'none';
    selBtn.textContent = selecting ? 'Auswahl beenden' : 'Auswählen';
    if (!selecting && grid) [].forEach.call(grid.querySelectorAll('.picked'), function (a) { a.classList.remove('picked'); a.querySelector('.sel').checked = false; });
    updBulk();
  });
  function bulk(op) {
    var sel = picked();
    if (!sel.length) return fam.toast('Bitte erst Fotos auswählen.', true);
    if (op === 'delete' && !confirm(sel.length + ' Foto(s) endgültig löschen?')) return;
    var fd = new FormData();
    sel.forEach(function (i) { fd.append('ids[]', i); });
    fd.append('op', op); fd.append('album_id', $('#bulkAlbum').value);
    fam.fetch('photo_bulk', fd).then(function (j) { fam.result(j); });
  }
  if ($('#bulkDel')) { $('#bulkDel').onclick = function () { bulk('delete'); }; $('#bulkMove').onclick = function () { bulk('move'); }; }

  /* ── Lightbox ── */
  var lb = $('#lb'), cur = -1, data = null;
  function open(i) {
    if (i < 0 || i >= ids.length) return;
    cur = i;
    lb.classList.add('open'); document.body.style.overflow = 'hidden';
    $('#lbSide').innerHTML = '<p class="muted">Lädt …</p>';
    fam.fetch('photo_get', { id: ids[i] }).then(function (j) {
      if (!j.ok) { fam.toast(j.error, true); return; }
      data = j.photo; render();
    });
  }
  function close() { lb.classList.remove('open'); document.body.style.overflow = ''; cur = -1; }
  function render() {
    var p = data;
    $('#lbImg').src = p.src;
    $('#lbPrev').style.visibility = cur > 0 ? 'visible' : 'hidden';
    $('#lbNext').style.visibility = cur < ids.length - 1 ? 'visible' : 'hidden';
    var opts = '<option value="0">Kein Album</option>' + FOTOS.albums.map(function (a) { return '<option value="' + a.id + '"' + (a.id === p.album_id ? ' selected' : '') + '>' + esc(a.title) + '</option>'; }).join('');
    var h = '<h3>' + esc(p.user) + '</h3><div class="muted small">hochgeladen ' + esc(p.uploaded) + (p.taken ? ' · aufgenommen am ' + esc(p.taken) : '') + '</div>';
    if (p.can_edit) {
      h += '<div class="form-group" style="margin-top:1rem"><label>Bildunterschrift</label><textarea id="lbCap" rows="3" maxlength="500">' + esc(p.caption) + '</textarea></div>' +
           '<div class="form-group"><label>Album</label><select id="lbAlb">' + opts + '</select></div>' +
           '<button class="btn btn-lime btn-sm" id="lbSave">Speichern</button>';
    } else if (p.caption) h += '<div class="cap">' + esc(p.caption) + '</div>';
    h += '<div class="actions"><button type="button" class="react' + (p.mine ? ' on' : '') + '" id="lbHeart"><span class="heart">' + (p.mine ? '♥' : '♡') + '</span> <span class="n">' + p.hearts + '</span></button>' +
         '<a class="btn btn-outline btn-sm" href="' + esc(p.dl) + '">Herunterladen</a>' +
         (p.can_edit ? '<button class="btn btn-danger btn-sm" id="lbDel">Löschen</button>' : '') + '</div>';
    h += '<h3 class="sub-h" style="margin-top:1.5rem">Kommentare (' + p.comments.length + ')</h3>';
    p.comments.forEach(function (c) {
      h += '<div class="comment"><div class="c-body"><div class="c-head"><strong>' + esc(c.name) + '</strong> <span class="muted small">' + esc(c.when) + '</span>' +
           (c.can_delete ? ' <button class="link-btn" data-cdel="' + c.id + '">löschen</button>' : '') + '</div><div class="c-text">' + esc(c.body).replace(/\n/g, '<br>') + '</div></div></div>';
    });
    h += '<div class="comment-form"><textarea id="lbCom" rows="2" maxlength="2000" placeholder="Kommentar schreiben …"></textarea><button class="btn btn-lime btn-sm" id="lbSend">Senden</button></div>';
    $('#lbSide').innerHTML = h;
    var s;
    if ((s = $('#lbSave'))) s.onclick = function () {
      fam.fetch('photo_update', { id: p.id, caption: $('#lbCap').value, album_id: $('#lbAlb').value }).then(function (j) { fam.toast(j.ok ? 'Gespeichert.' : j.error, !j.ok); });
    };
    if ((s = $('#lbDel'))) s.onclick = function () {
      if (!confirm('Dieses Foto endgültig löschen?')) return;
      fam.fetch('photo_delete', { id: p.id }).then(function (j) {
        if (!j.ok) return fam.toast(j.error, true);
        var a = grid.querySelector('[data-id="' + p.id + '"]'); if (a) a.remove();
        ids.splice(cur, 1); fam.toast('Gelöscht.');
        if (!ids.length) location.reload(); else open(Math.min(cur, ids.length - 1));
      });
    };
    $('#lbHeart').onclick = function () {
      fam.fetch('react', { type: 'photo', id: p.id }).then(function (j) { if (j.ok) { p.mine = j.mine; p.hearts = j.count; render(); } });
    };
    $('#lbSend').onclick = function () {
      var b = $('#lbCom').value.trim(); if (!b) return;
      fam.fetch('comment_add', { type: 'photo', id: p.id, body: b }).then(function (j) { if (j.ok) open(cur); else fam.toast(j.error, true); });
    };
    [].forEach.call($('#lbSide').querySelectorAll('[data-cdel]'), function (btn) {
      btn.onclick = function () { if (confirm('Kommentar löschen?')) fam.fetch('comment_delete', { id: btn.dataset.cdel }).then(function () { open(cur); }); };
    });
  }
  if (grid) grid.addEventListener('click', function (e) {
    var a = e.target.closest('.thumb'); if (!a) return;
    e.preventDefault();
    if (selecting) { a.classList.toggle('picked'); a.querySelector('.sel').checked = a.classList.contains('picked'); updBulk(); return; }
    open(ids.indexOf(+a.dataset.id));
  });
  $('#lbClose').onclick = close;
  $('#lbPrev').onclick = function () { open(cur - 1); };
  $('#lbNext').onclick = function () { open(cur + 1); };
  document.addEventListener('keydown', function (e) {
    if (!lb.classList.contains('open')) return;
    var t = e.target.tagName;
    if (e.key === 'Escape') close();
    if (t === 'TEXTAREA' || t === 'INPUT' || t === 'SELECT') return;
    if (e.key === 'ArrowLeft') open(cur - 1);
    if (e.key === 'ArrowRight') open(cur + 1);
  });
  // Direktlink: fotos.php?open=ID
  var m = /[?&]open=(\d+)/.exec(location.search);
  if (m && ids.indexOf(+m[1]) > -1) open(ids.indexOf(+m[1]));

  /* ── Upload ── */
  var drop = $('#drop'), fileIn = $('#fileIn'), queue = $('#queue'), busy = false, pending = [], doneCount = 0;
  drop.addEventListener('click', function () { fileIn.click(); });
  ['dragover', 'dragenter'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('over'); }); });
  ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('over'); }); });
  drop.addEventListener('drop', function (e) { add(e.dataTransfer.files); });
  fileIn.addEventListener('change', function () { add(fileIn.files); fileIn.value = ''; });
  function add(files) {
    [].forEach.call(files, function (f) {
      if (!/^image\//.test(f.type)) return fam.toast(f.name + ' ist kein Bild.', true);
      var row = document.createElement('div'); row.className = 'up-row';
      row.innerHTML = '<span class="nm">' + esc(f.name) + '</span><span class="bar"><i></i></span><span class="st">wartet</span>';
      queue.appendChild(row); pending.push({ f: f, row: row });
    });
    next();
  }
  function next() {
    if (busy || !pending.length) return;
    busy = true;
    var it = pending.shift(), st = $('.st', it.row), bar = $('i', it.row);
    st.textContent = 'verkleinert …';
    famResize(it.f).then(function (file) {
      st.textContent = 'lädt hoch …';
      var fd = new FormData(); fd.append('photo', file, file.name); fd.append('album_id', $('#upAlbum').value);
      return fam.fetch('photo_upload', fd, function (p) { bar.style.width = Math.round(p * 100) + '%'; });
    }).then(function (j) {
      if (j.ok) { st.textContent = 'fertig'; st.className = 'st ok'; bar.style.width = '100%'; doneCount++; $('#upDone').style.display = ''; }
      else { st.textContent = j.error || 'Fehler'; st.className = 'st err'; }
      busy = false; next();
    });
  }
  $('#upDone').onclick = function () { location.reload(); };

  /* ── Alben ── */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-album-new],[data-album-edit]'); if (!b) return;
    var f = $('#albForm'), d = b.dataset.albumEdit ? JSON.parse(b.dataset.albumEdit) : { id: 0, title: '', description: '' };
    f.elements.id.value = d.id; f.elements.title.value = d.title; f.elements.description.value = d.description;
    $('#albTitleH').textContent = d.id ? 'Album bearbeiten' : 'Neues Album';
    $('#albDel').style.display = d.id ? '' : 'none';
    $('#albDel').dataset.id = d.id;
  });
  $('#albDel').addEventListener('click', function () {
    if (!confirm('Album löschen? Die Fotos bleiben erhalten.')) return;
    fam.fetch('album_delete', { id: this.dataset.id }).then(function (j) { fam.result(j); });
  });
})();
