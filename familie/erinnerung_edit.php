<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$id = get_int('id');
$m = $id ? row('SELECT * FROM memories WHERE id = ?', array($id)) : null;
if ($id && !$m) { flash('Diese Geschichte gibt es nicht.', 'err'); redirect('erinnerungen.php'); }
if ($m && !is_owner_or_admin($user, $m['user_id'])) { flash('Du darfst nur deine eigenen Geschichten bearbeiten.', 'err'); redirect('erinnerung.php?id=' . $id); }
$kinds = array('story' => 'Geschichte', 'anecdote' => 'Anekdote', 'tradition' => 'Tradition', 'milestone' => 'Meilenstein');
$persons = rows('SELECT id, first_name, last_name FROM persons ORDER BY first_name COLLATE NOCASE, last_name COLLATE NOCASE');
$selected = $m ? array_map('intval', array_column(rows('SELECT person_id FROM memory_people WHERE memory_id = ?', array($id)), 'person_id')) : array();
$errors = array();

if (is_post()) {
    csrf_check();
    $title = post_str('title'); $body = post_str('body');
    $kind = isset($kinds[post_str('kind')]) ? post_str('kind') : 'story';
    $yearS = post_str('year'); $year = null;
    if ($yearS !== '') { if (preg_match('/^\d{4}$/', $yearS) && (int)$yearS >= 1800 && (int)$yearS <= (int)date('Y') + 1) $year = (int)$yearS; else $errors[] = 'Das Jahr muss vierstellig sein, zum Beispiel 1974.'; }
    if ($title === '' || mb_strlen($title) > 140) $errors[] = 'Bitte gib einen Titel ein.';
    if (mb_strlen($body) < 10) $errors[] = 'Die Geschichte ist noch sehr kurz. Erzähl gern etwas mehr.';
    if (mb_strlen($body) > 30000) $errors[] = 'Die Geschichte ist zu lang (höchstens 30.000 Zeichen).';
    $pids = isset($_POST['people']) && is_array($_POST['people']) ? array_values(array_unique(array_map('intval', $_POST['people']))) : array();
    $selected = $pids;
    $image = $m ? $m['image'] : null; $old = null;
    if (!$errors && !empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        try { $res = image_save_upload($_FILES['image'], 'memories', 1800, 480); $old = $image; $image = $res['file']; }
        catch (Exception $e) { $errors[] = $e->getMessage(); }
    } elseif (!$errors && isset($_POST['remove_image']) && $image) { $old = $image; $image = null; }
    $m = array_merge($m ? $m : array('image' => null), array('title' => $title, 'body' => $body, 'kind' => $kind, 'year' => $year, 'date_text' => post_str('date_text')));
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($id) {
                q("UPDATE memories SET title=?, body=?, year=?, date_text=?, kind=?, image=?, updated_at=datetime('now') WHERE id=?", array($title, $body, $year, mb_substr(post_str('date_text'), 0, 80), $kind, $image, $id));
                q('DELETE FROM memory_people WHERE memory_id = ?', array($id));
            } else {
                q('INSERT INTO memories (user_id, title, body, year, date_text, kind, image) VALUES (?,?,?,?,?,?,?)', array($user['id'], $title, $body, $year, mb_substr(post_str('date_text'), 0, 80), $kind, $image));
                $id = last_id();
            }
            foreach ($pids as $pid) if (val('SELECT COUNT(*) FROM persons WHERE id = ?', array($pid))) q('INSERT OR IGNORE INTO memory_people (memory_id, person_id) VALUES (?, ?)', array($id, $pid));
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); throw $e; }
        if ($old) image_delete('memories', $old);
        flash('Die Geschichte wurde gespeichert.');
        redirect('erinnerung.php?id=' . $id);
    }
}
$v = function ($k, $d = '') use ($m) { return $m && isset($m[$k]) && $m[$k] !== null ? $m[$k] : $d; };

fam_start($id ? 'Geschichte bearbeiten' : 'Geschichte erzählen', 'erinnerungen', $user, array('narrow' => true));
fam_heading('Erinnerungen', $id ? 'Geschichte <em>bearbeiten</em>' : 'Geschichte <em>erzählen</em>');
foreach ($errors as $e) echo '<div class="alert alert-err">' . h($e) . '</div>';
?>
<form method="post" enctype="multipart/form-data" class="panel"><?= csrf_field() ?>
  <div class="form-group"><label>Titel</label><input name="title" required maxlength="140" value="<?= h($v('title')) ?>" placeholder="z. B. Als Opa den Traktor im Teich versenkte"></div>
  <div class="form-3">
    <div class="form-group"><label>Art</label><select name="kind"><?php foreach ($kinds as $k => $l): ?><option value="<?= $k ?>"<?= $v('kind', 'story') === $k ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach ?></select></div>
    <div class="form-group"><label>Jahr (optional)</label><input name="year" inputmode="numeric" maxlength="4" value="<?= h($v('year')) ?>" placeholder="1974"></div>
    <div class="form-group"><label>Zeitangabe (optional)</label><input name="date_text" maxlength="80" value="<?= h($v('date_text')) ?>" placeholder="z. B. Sommer, Ostern"></div>
  </div>
  <div class="form-group"><label>Geschichte</label><textarea name="body" rows="14" required><?= h($v('body')) ?></textarea><div class="hint">Leere Zeilen trennen Absätze. Web-Adressen werden automatisch zu Links.</div></div>
  <?php if ($persons): ?>
  <div class="form-group"><label>Wer kommt vor? (optional)</label>
    <div class="pick-list"><?php foreach ($persons as $p): ?><label><input type="checkbox" name="people[]" value="<?= (int)$p['id'] ?>"<?= in_array((int)$p['id'], $selected, true) ? ' checked' : '' ?>> <?= h(person_label($p)) ?></label><?php endforeach ?></div></div>
  <?php endif ?>
  <div class="form-group"><label>Bild (optional)</label>
    <?php if ($v('image')): ?><div style="margin-bottom:.6rem"><img src="<?= h(img_url('memories', $v('image'), true)) ?>" alt="" style="max-width:200px;display:block;margin-bottom:.4rem"><label class="check"><input type="checkbox" name="remove_image" value="1"> Bild entfernen</label></div><?php endif ?>
    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></div>
  <div class="row"><button class="btn btn-lime">Speichern</button><a class="btn btn-outline" href="<?= $id ? 'erinnerung.php?id=' . $id : 'erinnerungen.php' ?>">Abbrechen</a></div>
</form>
<?php fam_end();
