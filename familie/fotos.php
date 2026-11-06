<?php
require_once __DIR__ . '/layout.php';
$user = require_login();

$per = 48;
$albumSel = get_str('album');
$byUser = get_int('user');
$sort = get_str('sort', 'new');
$search = get_str('q');
$page = max(1, get_int('page', 1));

$where = array(); $par = array();
if ($albumSel === 'none') $where[] = 'p.album_id IS NULL';
elseif ($albumSel !== '' && ctype_digit($albumSel)) { $where[] = 'p.album_id = ?'; $par[] = (int)$albumSel; }
if ($byUser) { $where[] = 'p.user_id = ?'; $par[] = $byUser; }
if ($search !== '') { $where[] = '(p.caption LIKE ? OR p.orig_name LIKE ?)'; $par[] = '%' . $search . '%'; $par[] = '%' . $search . '%'; }
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$order = $sort === 'old' ? 'p.id ASC' : ($sort === 'taken' ? "COALESCE(p.taken_at, substr(p.created_at,1,10)) DESC, p.id DESC" : 'p.id DESC');

$total = (int)val("SELECT COUNT(*) FROM photos p $W", $par);
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) $page = $pages;
$photos = rows("SELECT p.*, (SELECT COUNT(*) FROM reactions r WHERE r.target_type='photo' AND r.target_id=p.id) hearts,
                (SELECT COUNT(*) FROM comments c WHERE c.target_type='photo' AND c.target_id=p.id) ncom
                FROM photos p $W ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $par);

$albums = rows('SELECT a.*, (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) n,
                (SELECT file FROM photos p WHERE p.album_id = a.id ORDER BY p.id DESC LIMIT 1) cover FROM albums a ORDER BY a.title COLLATE NOCASE');
$allCount = (int)val('SELECT COUNT(*) FROM photos');
$curAlbum = ($albumSel !== '' && ctype_digit($albumSel)) ? row('SELECT * FROM albums WHERE id = ?', array((int)$albumSel)) : null;
$uploaders = rows('SELECT u.id, u.name, COUNT(p.id) n FROM users u JOIN photos p ON p.user_id = u.id GROUP BY u.id ORDER BY u.name COLLATE NOCASE');

function foto_url($over = array()) {
    $q = array_merge(array('album' => get_str('album'), 'user' => get_int('user') ?: '', 'sort' => get_str('sort', 'new'), 'q' => get_str('q'), 'page' => get_int('page', 1)), $over);
    foreach ($q as $k => $v) { if ($v === '' || $v === 0 || ($k === 'sort' && $v === 'new') || ($k === 'page' && (int)$v <= 1)) unset($q[$k]); }
    return 'fotos.php' . ($q ? '?' . http_build_query($q) : '');
}

$albumOptions = array();
foreach ($albums as $a) $albumOptions[] = array('id' => (int)$a['id'], 'title' => $a['title']);

fam_start('Fotos', 'fotos', $user);
fam_heading('Erinnerungen in Bildern', 'Foto<em>galerie</em>',
    '<button type="button" class="btn btn-lime btn-sm" data-open="upOverlay">Fotos hochladen</button>'
  . '<button type="button" class="btn btn-outline btn-sm" data-open="albOverlay" data-album-new>Neues Album</button>'
  . '<button type="button" class="btn btn-outline btn-sm" id="selToggle">Auswählen</button>');
?>
<div class="album-row">
  <a class="album<?= $albumSel === '' ? ' on' : '' ?>" href="<?= h(foto_url(array('album' => '', 'page' => 1))) ?>"><div class="cover">▦</div><div class="a-t">Alle Fotos</div><div class="a-c"><?= $allCount ?> Fotos</div></a>
  <?php foreach ($albums as $a): ?>
  <a class="album<?= (string)$a['id'] === $albumSel ? ' on' : '' ?>" href="<?= h(foto_url(array('album' => $a['id'], 'page' => 1))) ?>"><div class="cover"><?php if ($a['cover']): ?><img src="<?= h(img_url('photos', $a['cover'], true)) ?>" alt="" loading="lazy"><?php else: ?>▢<?php endif ?></div><div class="a-t"><?= h($a['title']) ?></div><div class="a-c"><?= (int)$a['n'] ?> Fotos</div></a>
  <?php endforeach ?>
</div>

<form class="toolbar no-print" method="get" action="fotos.php">
  <?php if ($albumSel !== ''): ?><input type="hidden" name="album" value="<?= h($albumSel) ?>"><?php endif ?>
  <input type="search" name="q" value="<?= h($search) ?>" placeholder="Bildunterschrift suchen …">
  <select name="user" onchange="this.form.submit()"><option value="">Alle Uploader</option>
    <?php foreach ($uploaders as $u): ?><option value="<?= (int)$u['id'] ?>"<?= $byUser === (int)$u['id'] ? ' selected' : '' ?>><?= h($u['name']) ?> (<?= (int)$u['n'] ?>)</option><?php endforeach ?></select>
  <select name="sort" onchange="this.form.submit()">
    <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Neueste zuerst</option>
    <option value="old"<?= $sort === 'old' ? ' selected' : '' ?>>Älteste zuerst</option>
    <option value="taken"<?= $sort === 'taken' ? ' selected' : '' ?>>Nach Aufnahmedatum</option></select>
  <button class="btn btn-outline btn-sm">Suchen</button>
  <?php if ($curAlbum && is_owner_or_admin($user, $curAlbum['created_by'])): ?>
    <button type="button" class="btn btn-outline btn-sm" data-open="albOverlay" data-album-edit="<?= h(json_attr(array('id' => (int)$curAlbum['id'], 'title' => $curAlbum['title'], 'description' => (string)$curAlbum['description']))) ?>">Album bearbeiten</button>
  <?php endif ?>
</form>
<?php if ($curAlbum && $curAlbum['description']): ?><p class="muted small" style="margin-bottom:1rem;font-family:var(--ffi)"><?= h($curAlbum['description']) ?></p><?php endif ?>

<div id="bulkBar" class="panel no-print" style="display:none;margin-bottom:1rem;padding:.8rem 1rem">
  <div class="row"><strong id="bulkN">0</strong> ausgewählt
    <select id="bulkAlbum" style="background:#121612;color:#e8ecf0;border:1px solid var(--border);padding:.4rem"><option value="0">Kein Album</option><?php foreach ($albumOptions as $a): ?><option value="<?= $a['id'] ?>"><?= h($a['title']) ?></option><?php endforeach ?></select>
    <button type="button" class="btn btn-outline btn-sm" id="bulkMove">In Album verschieben</button>
    <button type="button" class="btn btn-danger btn-sm" id="bulkDel">Löschen</button></div>
  <p class="muted tiny" style="margin-top:.4rem">Verschieben und Löschen wirkt nur auf deine eigenen Fotos (Admins dürfen alle).</p>
</div>

<?php if (!$photos): ?>
  <div class="empty">Hier sind noch keine Fotos. Mit „Fotos hochladen“ fängt die Sammlung an.</div>
<?php else: ?>
<div class="photo-grid" id="grid">
  <?php foreach ($photos as $p): ?>
  <a class="thumb" href="<?= h(img_url('photos', $p['file'])) ?>" data-id="<?= (int)$p['id'] ?>">
    <input type="checkbox" class="sel" tabindex="-1" aria-label="Foto auswählen">
    <img src="<?= h(img_url('photos', $p['file'], true)) ?>" alt="<?= h($p['caption'] ? $p['caption'] : 'Foto von ' . user_name($p['user_id'])) ?>" loading="lazy" width="240" height="240">
    <span class="meta"><span><?= h(user_name($p['user_id'])) ?></span><?php if ($p['hearts']): ?><span>♥ <?= (int)$p['hearts'] ?></span><?php endif ?><?php if ($p['ncom']): ?><span>💬 <?= (int)$p['ncom'] ?></span><?php endif ?></span>
  </a>
  <?php endforeach ?>
</div>
<?php if ($pages > 1): ?>
<div class="pager no-print">
  <?php for ($i = 1; $i <= $pages; $i++): ?><a class="chip<?= $i === $page ? ' on' : '' ?>" href="<?= h(foto_url(array('page' => $i))) ?>"><?= $i ?></a><?php endfor ?>
</div>
<?php endif ?>
<p class="muted tiny" style="margin-top:1rem"><?= $total ?> <?= $total === 1 ? 'Foto' : 'Fotos' ?></p>
<?php endif ?>

<!-- Upload -->
<div class="overlay" id="upOverlay"><div class="modal">
  <button class="modal-close" data-close="upOverlay" aria-label="Schließen">×</button>
  <h2>Fotos hochladen</h2>
  <div class="form-group"><label>Album (optional)</label>
    <select id="upAlbum"><option value="0">Kein Album</option>
    <?php foreach ($albumOptions as $a): ?><option value="<?= $a['id'] ?>"<?= $curAlbum && (int)$curAlbum['id'] === $a['id'] ? ' selected' : '' ?>><?= h($a['title']) ?></option><?php endforeach ?></select></div>
  <div class="drop" id="drop"><strong>Bilder hierher ziehen</strong> oder klicken zum Auswählen<br><span class="tiny">JPG, PNG, GIF oder WebP · große Bilder werden automatisch verkleinert</span>
    <input type="file" id="fileIn" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden></div>
  <div id="queue" style="margin-top:1rem"></div>
  <div class="row" style="margin-top:1rem"><button class="btn btn-lime btn-sm" id="upDone" style="display:none" type="button">Fertig, Galerie neu laden</button></div>
</div></div>

<!-- Album -->
<div class="overlay" id="albOverlay"><div class="modal">
  <button class="modal-close" data-close="albOverlay" aria-label="Schließen">×</button>
  <h2 id="albTitleH">Neues Album</h2>
  <form data-api="album_save" id="albForm"><input type="hidden" name="id" value="0">
    <div class="form-group"><label>Titel</label><input name="title" required maxlength="80"></div>
    <div class="form-group"><label>Beschreibung (optional)</label><textarea name="description" rows="3" maxlength="500"></textarea></div>
    <div class="row between"><button class="btn btn-lime btn-sm" type="submit">Speichern</button>
    <button type="button" class="btn btn-danger btn-sm" id="albDel" style="display:none">Album löschen</button></div>
  </form>
</div></div>

<!-- Lightbox -->
<div class="lb" id="lb" aria-modal="true" role="dialog">
  <div class="lb-stage"><button class="lb-close" id="lbClose" aria-label="Schließen">×</button>
    <button class="lb-btn lb-prev" id="lbPrev" aria-label="Zurück">‹</button><img id="lbImg" alt="">
    <button class="lb-btn lb-next" id="lbNext" aria-label="Weiter">›</button></div>
  <div class="lb-side" id="lbSide"></div>
</div>
<?php
fam_end('<script>window.FOTOS=' . json_attr(array('albums' => $albumOptions, 'me' => (int)$user['id'])) . ';</script><script src="fotos.js?v=' . (int)@filemtime(__DIR__ . '/fotos.js') . '"></script>');
