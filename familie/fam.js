/* Familienbereich: gemeinsame Helfer */
(function () {
  'use strict';
  var meta = document.querySelector('meta[name="csrf"]');
  var CSRF = meta ? meta.content : '';

  function toast(msg, isErr) {
    var wrap = document.querySelector('.toast-wrap');
    if (!wrap) { wrap = document.createElement('div'); wrap.className = 'toast-wrap'; document.body.appendChild(wrap); }
    var t = document.createElement('div');
    t.className = 'toast' + (isErr ? ' err' : '');
    t.textContent = msg;
    wrap.appendChild(t);
    setTimeout(function () { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; }, 3200);
    setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 3600);
  }

  function toForm(data) {
    if (data instanceof FormData) return data;
    var fd = new FormData();
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fd;
  }

  /** Aufruf von api.php. Rückgabe: Promise mit dem JSON-Objekt (ok: true/false). */
  function famFetch(action, data, onProgress) {
    var fd = toForm(data);
    fd.set('action', action);
    return new Promise(function (resolve) {
      var x = new XMLHttpRequest();
      x.open('POST', 'api.php');
      x.setRequestHeader('X-CSRF', CSRF);
      x.setRequestHeader('Accept', 'application/json');
      if (onProgress && x.upload) x.upload.onprogress = function (e) { if (e.lengthComputable) onProgress(e.loaded / e.total); };
      x.onload = function () {
        var j;
        try { j = JSON.parse(x.responseText); } catch (e) { j = { ok: false, error: 'Unerwartete Antwort vom Server (' + x.status + ').' }; }
        resolve(j);
      };
      x.onerror = function () { resolve({ ok: false, error: 'Keine Verbindung zum Server.' }); };
      x.send(fd);
    });
  }

  function handleResult(j, form) {
    if (!j.ok) { toast(j.error || 'Das hat nicht geklappt.', true); return false; }
    if (j.redirect) { location.href = j.redirect; return true; }
    if (j.reload) { location.reload(); return true; }
    if (j.msg) toast(j.msg);
    if (form && j.reset) form.reset();
    return true;
  }

  window.fam = { fetch: famFetch, toast: toast, csrf: CSRF, result: handleResult };

  // Bestätigungsdialog für Links, Buttons und Formulare
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (el && el.tagName !== 'FORM' && !confirm(el.getAttribute('data-confirm'))) { e.preventDefault(); e.stopPropagation(); }
  }, true);
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.hasAttribute('data-confirm') && !confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  }, true);

  // Formulare, die per fetch an api.php gehen
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.hasAttribute || !f.hasAttribute('data-api') || e.defaultPrevented) return;
    e.preventDefault();
    var btn = f.querySelector('[type=submit]');
    if (btn) btn.disabled = true;
    famFetch(f.getAttribute('data-api'), new FormData(f)).then(function (j) {
      if (btn) btn.disabled = false;
      handleResult(j, f);
    });
  });

  // Einzelne Aktionen per Klick
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-api-click]');
    if (!el || e.defaultPrevented) return;
    e.preventDefault();
    var d = {};
    Object.keys(el.dataset).forEach(function (k) { if (k !== 'apiClick' && k !== 'confirm') d[k] = el.dataset[k]; });
    famFetch(el.getAttribute('data-api-click'), d).then(function (j) { handleResult(j); });
  });

  // Herzen
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-react]');
    if (!b) return;
    e.preventDefault();
    var p = b.getAttribute('data-react').split(':');
    famFetch('react', { type: p[0], id: p[1] }).then(function (j) {
      if (!j.ok) return toast(j.error || 'Fehler', true);
      b.classList.toggle('on', !!j.mine);
      b.querySelector('.heart').textContent = j.mine ? '♥' : '♡';
      b.querySelector('.n').textContent = j.count;
    });
  });

  // In die Zwischenablage kopieren
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) return;
    var inp = document.querySelector(b.getAttribute('data-copy'));
    if (!inp) return;
    inp.select();
    var done = function () { toast('Kopiert.'); };
    if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(inp.value).then(done, function () { document.execCommand('copy'); done(); });
    else { document.execCommand('copy'); done(); }
  });

  // Modale Fenster der Familienseiten (nutzen das Modal-Muster der Website)
  window.famOpen = function (id) { var o = document.getElementById(id); if (o) { o.classList.add('open'); document.body.style.overflow = 'hidden'; } };
  window.famClose = function (id) { var o = document.getElementById(id); if (o) { o.classList.remove('open'); document.body.style.overflow = ''; } };
  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-close]');
    if (c) famClose(c.getAttribute('data-close'));
    var o = e.target.closest('[data-open]');
    if (o) famOpen(o.getAttribute('data-open'));
  });

  /** Clientseitige Verkleinerung vor dem Upload. Gibt ein Promise mit einem File/Blob zurück. */
  window.famResize = function (file, maxEdge, quality) {
    maxEdge = maxEdge || 2400; quality = quality || 0.86;
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < 600 * 1024) return resolve(file);
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        URL.revokeObjectURL(url);
        var w = img.naturalWidth, h = img.naturalHeight, s = Math.min(1, maxEdge / Math.max(w, h));
        if (s === 1 && file.size < 3 * 1024 * 1024) return resolve(file);
        var c = document.createElement('canvas');
        c.width = Math.round(w * s); c.height = Math.round(h * s);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        c.toBlob(function (b) {
          if (!b || b.size >= file.size) return resolve(file);
          // EXIF geht beim Neuzeichnen verloren, die Drehung übernimmt der Browser
          var f = new File([b], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: file.lastModified });
          resolve(f);
        }, 'image/jpeg', quality);
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      img.src = url;
    });
  };
})();
