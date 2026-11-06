<?php
require_once __DIR__ . '/layout.php';
$user = require_role('editor');

$id = get_int('id');
$p = $id ? row('SELECT * FROM persons WHERE id = ?', array($id)) : null;
if ($id && !$p) { flash('Diese Person gibt es nicht.', 'err'); redirect('stammbaum.php'); }
$rel = get_str('rel'); $of = get_int('of');
$ofP = ($rel !== '' && $of) ? row('SELECT * FROM persons WHERE id = ?', array($of)) : null;
if (!in_array($rel, array('child', 'partner', 'parent'), true)) { $rel = ''; $ofP = null; }

function descendants($pid) {
    $seen = array(); $stack = array($pid);
    while ($stack) {
        $x = array_pop($stack);
        foreach (rows('SELECT child_id FROM parent_links WHERE parent_id = ?', array($x)) as $c) {
            if (!isset($seen[(int)$c['child_id']])) { $seen[(int)$c['child_id']] = 1; $stack[] = (int)$c['child_id']; }
        }
    }
    return array_keys($seen);
}
function pdate_in($key, &$errors, $label) {
    $raw = post_str($key);
    $d = parse_partial_date($raw);
    if ($d === false) { $errors[] = $label . ': Bitte TT.MM.JJJJ, MM.JJJJ oder nur das Jahr eingeben.'; return null; }
    return $d;
}

if (is_post()) {
    csrf_check();
    $do = post_str('do', 'save');
    $back = $id ? 'person_edit.php?id=' . $id : 'stammbaum.php';

    if ($id && $do === 'delete') {
        image_delete('persons', $p['photo']);
        q('DELETE FROM persons WHERE id = ?', array($id));
        flash('Die Person wurde gelöscht.');
        redirect('stammbaum.php');
    }
    if ($id && $do === 'add_parent') {
        $pid = post_int('other');
        if (!$pid || $pid === $id || !val('SELECT COUNT(*) FROM persons WHERE id = ?', array($pid))) flash('Bitte wähle eine Person.', 'err');
        elseif (in_array($pid, descendants($id), true)) flash('Das geht nicht: Diese Person ist ein Nachkomme.', 'err');
        elseif ((int)val('SELECT COUNT(*) FROM parent_links WHERE child_id = ?', array($id)) >= 4) flash('Mehr als vier Elternteile sind nicht möglich.', 'err');
        else { q('INSERT OR IGNORE INTO parent_links (parent_id, child_id, kind) VALUES (?, ?, ?)', array($pid, $id, post_str('pkind') === 'adopted' ? 'adopted' : 'bio')); flash('Elternteil hinzugefügt.'); }
        redirect($back);
    }
    if ($id && $do === 'add_child') {
        $cid = post_int('other');
        if (!$cid || $cid === $id || !val('SELECT COUNT(*) FROM persons WHERE id = ?', array($cid))) flash('Bitte wähle eine Person.', 'err');
        elseif (in_array($id, descendants($cid), true)) flash('Das geht nicht: Diese Person ist ein Vorfahre.', 'err');
        else { q('INSERT OR IGNORE INTO parent_links (parent_id, child_id, kind) VALUES (?, ?, ?)', array($id, $cid, post_str('pkind') === 'adopted' ? 'adopted' : 'bio')); flash('Kind hinzugefügt.'); }
        redirect($back);
    }
    if ($id && $do === 'unlink') {
        q('DELETE FROM parent_links WHERE parent_id = ? AND child_id = ?', array(post_int('parent'), post_int('child')));
        flash('Die Verbindung wurde entfernt.');
        redirect($back);
    }
    if ($id && $do === 'add_partner') {
        $pid = post_int('other'); $errs = array();
        $start = pdate_in('start_date', $errs, 'Beginn'); $end = pdate_in('end_date', $errs, 'Ende');
        $kind = in_array(post_str('kind'), array('marriage', 'partnership', 'other'), true) ? post_str('kind') : 'marriage';
        if (!$pid || $pid === $id || !val('SELECT COUNT(*) FROM persons WHERE id = ?', array($pid))) $errs[] = 'Bitte wähle eine Person.';
        elseif (val('SELECT COUNT(*) FROM partnerships WHERE (person_a = ? AND person_b = ?) OR (person_a = ? AND person_b = ?)', array($id, $pid, $pid, $id))) $errs[] = 'Diese Verbindung gibt es schon.';
        if ($errs) flash(implode(' ', $errs), 'err');
        else { q('INSERT INTO partnerships (person_a, person_b, kind, start_date, end_date) VALUES (?, ?, ?, ?, ?)', array($id, $pid, $kind, $start, $end)); flash('Partner hinzugefügt.'); }
        redirect($back);
    }
    if ($id && $do === 'save_partner') {
        $sid = post_int('sid'); $errs = array();
        $start = pdate_in('start_date', $errs, 'Beginn'); $end = pdate_in('end_date', $errs, 'Ende');
        $kind = in_array(post_str('kind'), array('marriage', 'partnership', 'other'), true) ? post_str('kind') : 'marriage';
        if ($errs) flash(implode(' ', $errs), 'err');
        else { q('UPDATE partnerships SET kind = ?, start_date = ?, end_date = ? WHERE id = ? AND (person_a = ? OR person_b = ?)', array($kind, $start, $end, $sid, $id, $id)); flash('Gespeichert.'); }
        redirect($back);
    }
    if ($id && $do === 'del_partner') {
        q('DELETE FROM partnerships WHERE id = ? AND (person_a = ? OR person_b = ?)', array(post_int('sid'), $id, $id));
        flash('Die Partnerschaft wurde entfernt.');
        redirect($back);
    }

    // Person speichern
    $errors = array();
    $first = mb_substr(post_str('first_name'), 0, 80); $last = mb_substr(post_str('last_name'), 0, 80);
    if ($first === '' && $last === '') $errors[] = 'Bitte gib mindestens einen Vor- oder Nachnamen ein.';
    $bd = pdate_in('birth_date', $errors, 'Geburtsdatum'); $dd = pdate_in('death_date', $errors, 'Sterbedatum');
    if ($bd && $dd && strcmp(substr($bd, 0, 4), substr($dd, 0, 4)) > 0) $errors[] = 'Das Sterbejahr liegt vor dem Geburtsjahr.';
    $gender = in_array(post_str('gender'), array('m', 'f', 'd'), true) ? post_str('gender') : '';
    $uid = post_int('user_id') ?: null;
    if ($uid) {
        if (!val('SELECT COUNT(*) FROM users WHERE id = ?', array($uid))) $uid = null;
        elseif (val('SELECT COUNT(*) FROM persons WHERE user_id = ? AND id <> ?', array($uid, $id))) { $errors[] = 'Dieses Konto ist schon mit einer anderen Person verknüpft.'; }
    }
    $photo = $p ? $p['photo'] : null; $old = null;
    if (!$errors && !empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        try { $res = image_save_upload($_FILES['photo'], 'persons', 900, 240); $old = $photo; $photo = $res['file']; }
        catch (Exception $e) { $errors[] = $e->getMessage(); }
    } elseif (!$errors && isset($_POST['remove_photo']) && $photo) { $old = $photo; $photo = null; }

    $p = array_merge($p ? $p : array('photo' => null), array('first_name' => $first, 'last_name' => $last, 'birth_name' => post_str('birth_name'), 'gender' => $gender,
        'birth_date' => post_str('birth_date'), 'birth_place' => post_str('birth_place'), 'death_date' => post_str('death_date'), 'death_place' => post_str('death_place'),
        'bio' => post_str('bio'), 'user_id' => $uid));
    if (!$errors) {
        $vals = array($first, $last, mb_substr(post_str('birth_name'), 0, 80), $gender, $bd, mb_substr(post_str('birth_place'), 0, 100), $dd, mb_substr(post_str('death_place'), 0, 100), mb_substr(post_str('bio'), 0, 5000), $photo, $uid);
        if ($id) {
            q("UPDATE persons SET first_name=?, last_name=?, birth_name=?, gender=?, birth_date=?, birth_place=?, death_date=?, death_place=?, bio=?, photo=?, user_id=?, updated_at=datetime('now') WHERE id=?", array_merge($vals, array($id)));
        } else {
            q('INSERT INTO persons (first_name, last_name, birth_name, gender, birth_date, birth_place, death_date, death_place, bio, photo, user_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, array($user['id'])));
            $id = last_id();
            if ($ofP) {
                if ($rel === 'child') q('INSERT OR IGNORE INTO parent_links (parent_id, child_id) VALUES (?, ?)', array($ofP['id'], $id));
                elseif ($rel === 'parent') q('INSERT OR IGNORE INTO parent_links (parent_id, child_id) VALUES (?, ?)', array($id, $ofP['id']));
                else q("INSERT INTO partnerships (person_a, person_b, kind) VALUES (?, ?, 'marriage')", array($ofP['id'], $id));
            }
        }
        if ($old) image_delete('persons', $old);
        flash('Gespeichert.');
        redirect($ofP ? 'stammbaum.php?p=' . $id : 'person_edit.php?id=' . $id);
    }
} elseif (!$p && $ofP) {
    $p = array('last_name' => $rel === 'child' ? $ofP['last_name'] : '', 'gender' => '');
}

$v = function ($k, $d = '') use ($p) { return $p && isset($p[$k]) && $p[$k] !== null ? $p[$k] : $d; };
$allPersons = rows('SELECT id, first_name, last_name, birth_date FROM persons ORDER BY first_name COLLATE NOCASE, last_name COLLATE NOCASE');
$label = function ($x) { $y = preg_match('/^\d{4}/', (string)$x['birth_date'], $m) ? ' (* ' . $m[0] . ')' : ''; return person_label($x) . $y; };
$title = $id ? person_label($p) : 'Neue Person';

fam_start($id ? 'Person bearbeiten' : 'Neue Person', 'stammbaum', $user, array('narrow' => true));
fam_heading('Stammbaum', $id ? h($title) : 'Neue <em>Person</em>');
if (!empty($errors)) foreach ($errors as $e) echo '<div class="alert alert-err">' . h($e) . '</div>';
if ($ofP && !$id) echo '<div class="alert alert-info">Du legst ' . ($rel === 'child' ? 'ein Kind' : ($rel === 'partner' ? 'einen Partner' : 'einen Elternteil')) . ' von <strong>' . h(person_label($ofP)) . '</strong> an.</div>';
?>
<form method="post" enctype="multipart/form-data" class="panel"><?= csrf_field() ?><input type="hidden" name="do" value="save">
  <div class="form-3">
    <div class="form-group"><label>Vorname(n)</label><input name="first_name" maxlength="80" value="<?= h($v('first_name')) ?>"></div>
    <div class="form-group"><label>Nachname</label><input name="last_name" maxlength="80" value="<?= h($v('last_name')) ?>"></div>
    <div class="form-group"><label>Geburtsname</label><input name="birth_name" maxlength="80" value="<?= h($v('birth_name')) ?>"></div>
  </div>
  <div class="form-2">
    <div class="form-group"><label>Geschlecht</label><select name="gender"><option value="">unbekannt</option><option value="m"<?= $v('gender') === 'm' ? ' selected' : '' ?>>männlich</option><option value="f"<?= $v('gender') === 'f' ? ' selected' : '' ?>>weiblich</option><option value="d"<?= $v('gender') === 'd' ? ' selected' : '' ?>>divers</option></select></div>
    <div class="form-group"><label>Verknüpftes Konto (optional)</label><select name="user_id"><option value="">kein Konto</option>
      <?php foreach (rows('SELECT id, name FROM users WHERE active = 1 ORDER BY name COLLATE NOCASE') as $u): ?><option value="<?= (int)$u['id'] ?>"<?= (int)$v('user_id') === (int)$u['id'] ? ' selected' : '' ?>><?= h($u['name']) ?></option><?php endforeach ?></select></div>
  </div>
  <div class="form-2">
    <div class="form-group"><label>Geboren am</label><input name="birth_date" placeholder="TT.MM.JJJJ, MM.JJJJ oder JJJJ" value="<?= h(input_partial($v('birth_date'))) ?>"><div class="hint">Nur mit vollem Datum gibt es Geburtstags-Erinnerungen.</div></div>
    <div class="form-group"><label>Geburtsort</label><input name="birth_place" maxlength="100" value="<?= h($v('birth_place')) ?>"></div>
  </div>
  <div class="form-2">
    <div class="form-group"><label>Gestorben am</label><input name="death_date" placeholder="leer, wenn die Person lebt" value="<?= h(input_partial($v('death_date'))) ?>"></div>
    <div class="form-group"><label>Sterbeort</label><input name="death_place" maxlength="100" value="<?= h($v('death_place')) ?>"></div>
  </div>
  <div class="form-group"><label>Lebensgeschichte, Notizen</label><textarea name="bio" rows="6" maxlength="5000"><?= h($v('bio')) ?></textarea></div>
  <div class="form-group"><label>Foto (optional)</label>
    <?php if ($v('photo')): ?><div style="margin-bottom:.6rem"><img src="<?= h(img_url('persons', $v('photo'), true)) ?>" alt="" style="width:90px;height:90px;object-fit:cover;border-radius:50%;display:block;margin-bottom:.4rem"><label class="check"><input type="checkbox" name="remove_photo" value="1"> Foto entfernen</label></div><?php endif ?>
    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"></div>
  <div class="row"><button class="btn btn-lime">Speichern</button><a class="btn btn-outline" href="stammbaum.php<?= $id ? '?p=' . $id : '' ?>">Zurück zum Stammbaum</a></div>
</form>

<?php if ($id): 
  $parents = rows('SELECT p.*, l.kind lk FROM parent_links l JOIN persons p ON p.id = l.parent_id WHERE l.child_id = ?', array($id));
  $children = rows('SELECT p.*, l.kind lk FROM parent_links l JOIN persons p ON p.id = l.child_id WHERE l.parent_id = ? ORDER BY p.birth_date', array($id));
  $ships = rows('SELECT s.*, p.first_name, p.last_name, p.birth_date FROM partnerships s JOIN persons p ON p.id = CASE WHEN s.person_a = ? THEN s.person_b ELSE s.person_a END WHERE s.person_a = ? OR s.person_b = ?', array($id, $id, $id));
  $desc = descendants($id);
  $parentIds = array_map(function ($x) { return (int)$x['id']; }, $parents);
  $childIds = array_map(function ($x) { return (int)$x['id']; }, $children);
  $partnerIds = array_map(function ($s) use ($id) { return (int)($s['person_a'] == $id ? $s['person_b'] : $s['person_a']); }, $ships);
?>
<div class="panel">
  <h2>Eltern</h2>
  <?php foreach ($parents as $x): ?><div class="row between" style="padding:.4rem 0;border-top:1px solid rgba(255,255,255,.06)"><span><?= h($label($x)) ?><?= $x['lk'] === 'adopted' ? ' <span class="tag">adoptiert</span>' : '' ?></span>
    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="unlink"><input type="hidden" name="parent" value="<?= (int)$x['id'] ?>"><input type="hidden" name="child" value="<?= $id ?>"><button class="link-btn" style="color:#f87171">entfernen</button></form></div><?php endforeach ?>
  <form method="post" class="row" style="margin-top:.8rem"><?= csrf_field() ?><input type="hidden" name="do" value="add_parent">
    <select name="other" style="background:#121612;color:#e8ecf0;border:1px solid var(--border);padding:.5rem;max-width:260px"><option value="">Elternteil wählen …</option>
      <?php foreach ($allPersons as $x): if ($x['id'] == $id || in_array((int)$x['id'], $parentIds, true) || in_array((int)$x['id'], $desc, true)) continue; ?><option value="<?= (int)$x['id'] ?>"><?= h($label($x)) ?></option><?php endforeach ?></select>
    <select name="pkind" style="background:#121612;color:#e8ecf0;border:1px solid var(--border);padding:.5rem"><option value="bio">leiblich</option><option value="adopted">adoptiert</option></select>
    <button class="btn btn-outline btn-sm">Hinzufügen</button>
    <a class="link-btn" href="person_edit.php?rel=parent&of=<?= $id ?>">oder neue Person anlegen</a></form>
</div>

<div class="panel">
  <h2>Partner</h2>
  <?php foreach ($ships as $s): ?>
  <form method="post" style="padding:.7rem 0;border-top:1px solid rgba(255,255,255,.06)"><?= csrf_field() ?><input type="hidden" name="sid" value="<?= (int)$s['id'] ?>">
    <div class="row between"><strong><?= h($label($s)) ?></strong></div>
    <div class="form-3" style="margin-top:.5rem">
      <div class="form-group" style="margin:0"><label>Art</label><select name="kind"><option value="marriage"<?= $s['kind'] === 'marriage' ? ' selected' : '' ?>>Ehe</option><option value="partnership"<?= $s['kind'] === 'partnership' ? ' selected' : '' ?>>Partnerschaft</option><option value="other"<?= $s['kind'] === 'other' ? ' selected' : '' ?>>Sonstige</option></select></div>
      <div class="form-group" style="margin:0"><label>Beginn</label><input name="start_date" value="<?= h(input_partial($s['start_date'])) ?>" placeholder="TT.MM.JJJJ"></div>
      <div class="form-group" style="margin:0"><label>Ende</label><input name="end_date" value="<?= h(input_partial($s['end_date'])) ?>" placeholder="leer = besteht"></div>
    </div>
    <div class="row" style="margin-top:.6rem"><button class="btn btn-outline btn-sm" name="do" value="save_partner">Speichern</button>
      <button class="btn btn-danger btn-sm" name="do" value="del_partner" data-confirm="Diese Partnerschaft entfernen?">Entfernen</button></div>
  </form>
  <?php endforeach ?>
  <form method="post" style="margin-top:1rem;border-top:1px solid var(--border);padding-top:1rem"><?= csrf_field() ?><input type="hidden" name="do" value="add_partner">
    <div class="form-3">
      <div class="form-group" style="margin:0"><label>Partner</label><select name="other"><option value="">wählen …</option>
        <?php foreach ($allPersons as $x): if ($x['id'] == $id || in_array((int)$x['id'], $partnerIds, true)) continue; ?><option value="<?= (int)$x['id'] ?>"><?= h($label($x)) ?></option><?php endforeach ?></select></div>
      <div class="form-group" style="margin:0"><label>Art</label><select name="kind"><option value="marriage">Ehe</option><option value="partnership">Partnerschaft</option><option value="other">Sonstige</option></select></div>
      <div class="form-group" style="margin:0"><label>Hochzeit / Beginn</label><input name="start_date" placeholder="TT.MM.JJJJ"></div>
    </div>
    <div class="row" style="margin-top:.6rem"><button class="btn btn-outline btn-sm">Partner hinzufügen</button><a class="link-btn" href="person_edit.php?rel=partner&of=<?= $id ?>">oder neue Person anlegen</a></div>
  </form>
</div>

<div class="panel">
  <h2>Kinder</h2>
  <?php foreach ($children as $x): ?><div class="row between" style="padding:.4rem 0;border-top:1px solid rgba(255,255,255,.06)"><span><a href="person_edit.php?id=<?= (int)$x['id'] ?>"><?= h($label($x)) ?></a><?= $x['lk'] === 'adopted' ? ' <span class="tag">adoptiert</span>' : '' ?></span>
    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="unlink"><input type="hidden" name="parent" value="<?= $id ?>"><input type="hidden" name="child" value="<?= (int)$x['id'] ?>"><button class="link-btn" style="color:#f87171">entfernen</button></form></div><?php endforeach ?>
  <form method="post" class="row" style="margin-top:.8rem"><?= csrf_field() ?><input type="hidden" name="do" value="add_child">
    <select name="other" style="background:#121612;color:#e8ecf0;border:1px solid var(--border);padding:.5rem;max-width:260px"><option value="">Kind wählen …</option>
      <?php $anc = array(); foreach ($allPersons as $x) { if ($x['id'] == $id || in_array((int)$x['id'], $childIds, true)) continue; if (in_array($id, descendants((int)$x['id']), true)) continue; ?><option value="<?= (int)$x['id'] ?>"><?= h($label($x)) ?></option><?php } ?></select>
    <select name="pkind" style="background:#121612;color:#e8ecf0;border:1px solid var(--border);padding:.5rem"><option value="bio">leiblich</option><option value="adopted">adoptiert</option></select>
    <button class="btn btn-outline btn-sm">Hinzufügen</button>
    <a class="link-btn" href="person_edit.php?rel=child&of=<?= $id ?>">oder neue Person anlegen</a></form>
</div>

<div class="panel">
  <h2>Person löschen</h2>
  <p class="muted small" style="margin-bottom:1rem">Entfernt die Person samt ihren Verbindungen aus dem Stammbaum. Geschichten bleiben erhalten.</p>
  <form method="post" data-confirm="<?= h(person_label($p)) ?> endgültig löschen?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger btn-sm">Person löschen</button></form>
</div>
<?php endif;
fam_end();
