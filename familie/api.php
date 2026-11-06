<?php
// JSON-Schnittstelle für Aktionen, die ohne Seitenwechsel laufen (Herzen, Kommentare, Uploads ...)
require_once __DIR__ . '/auth.php';

if (!is_post()) json_out(array('ok' => false, 'error' => 'Nur POST erlaubt.'), 405);
$user = auth_user();
if (!$user) json_out(array('ok' => false, 'error' => 'Du bist nicht angemeldet.'), 401);
if ((int)$user['must_change'] === 1) json_out(array('ok' => false, 'error' => 'Bitte ändere zuerst dein Passwort.'), 403);
csrf_check();

function fail($msg, $code = 400) { json_out(array('ok' => false, 'error' => $msg), $code); }
function ok($extra = array()) { json_out(array_merge(array('ok' => true), $extra)); }

$targets = array('photo' => 'photos', 'memory' => 'memories', 'post' => 'posts', 'recipe' => 'recipes');
$action = post_str('action');

switch ($action) {

case 'react': {
    $type = post_str('type'); $id = post_int('id');
    if (!isset($targets[$type]) || !val('SELECT COUNT(*) FROM ' . $targets[$type] . ' WHERE id = ?', array($id))) fail('Eintrag nicht gefunden.', 404);
    $has = val('SELECT COUNT(*) FROM reactions WHERE target_type = ? AND target_id = ? AND user_id = ?', array($type, $id, $user['id']));
    if ($has) q('DELETE FROM reactions WHERE target_type = ? AND target_id = ? AND user_id = ?', array($type, $id, $user['id']));
    else q('INSERT INTO reactions (target_type, target_id, user_id) VALUES (?, ?, ?)', array($type, $id, $user['id']));
    ok(array('mine' => !$has, 'count' => (int)val('SELECT COUNT(*) FROM reactions WHERE target_type = ? AND target_id = ?', array($type, $id))));
}

case 'comment_add': {
    $type = post_str('type'); $id = post_int('id'); $body = post_str('body');
    if (!isset($targets[$type]) || !val('SELECT COUNT(*) FROM ' . $targets[$type] . ' WHERE id = ?', array($id))) fail('Eintrag nicht gefunden.', 404);
    if ($body === '' || mb_strlen($body) > 2000) fail('Der Kommentar muss zwischen 1 und 2000 Zeichen lang sein.');
    q('INSERT INTO comments (target_type, target_id, user_id, body) VALUES (?, ?, ?, ?)', array($type, $id, $user['id'], $body));
    ok(array('reload' => true));
}

case 'comment_delete': {
    $c = row('SELECT * FROM comments WHERE id = ?', array(post_int('id')));
    if (!$c) fail('Kommentar nicht gefunden.', 404);
    if (!is_owner_or_admin($user, $c['user_id'])) fail('Das darfst du nicht.', 403);
    q('DELETE FROM comments WHERE id = ?', array($c['id']));
    ok(array('reload' => true));
}

case 'photo_upload': {
    if (empty($_FILES['photo'])) fail('Keine Datei empfangen. Das Bild ist eventuell zu groß für den Server.');
    $album = post_int('album_id');
    if ($album && !val('SELECT COUNT(*) FROM albums WHERE id = ?', array($album))) $album = 0;
    try {
        $r = image_save_upload($_FILES['photo'], 'photos');
    } catch (Exception $e) { fail($e->getMessage()); }
    $name = isset($_FILES['photo']['name']) ? mb_substr(basename((string)$_FILES['photo']['name']), 0, 120) : '';
    q('INSERT INTO photos (user_id, album_id, file, orig_name, taken_at, width, height, bytes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
      array($user['id'], $album ? $album : null, $r['file'], $name, $r['taken'], $r['w'], $r['h'], $r['bytes']));
    ok(array('id' => last_id(), 'thumb' => img_url('photos', $r['file'], true)));
}

case 'photo_get': {
    $p = row('SELECT * FROM photos WHERE id = ?', array(post_int('id')));
    if (!$p) fail('Foto nicht gefunden.', 404);
    $n = (int)val('SELECT COUNT(*) FROM reactions WHERE target_type = ? AND target_id = ?', array('photo', $p['id']));
    $mine = (int)val('SELECT COUNT(*) FROM reactions WHERE target_type = ? AND target_id = ? AND user_id = ?', array('photo', $p['id'], $user['id'])) > 0;
    $cs = array();
    foreach (rows('SELECT * FROM comments WHERE target_type = ? AND target_id = ? ORDER BY id', array('photo', $p['id'])) as $c) {
        $cs[] = array('id' => (int)$c['id'], 'name' => user_name($c['user_id']), 'body' => $c['body'], 'when' => rel_time($c['created_at']),
                      'can_delete' => is_owner_or_admin($user, $c['user_id']));
    }
    ok(array('photo' => array(
        'id' => (int)$p['id'], 'src' => img_url('photos', $p['file']), 'dl' => img_url('photos', $p['file']) . '&dl=1',
        'caption' => (string)$p['caption'], 'user' => user_name($p['user_id']), 'uploaded' => rel_time($p['created_at']),
        'taken' => $p['taken_at'] ? de_date($p['taken_at']) : '', 'album_id' => (int)$p['album_id'],
        'can_edit' => is_owner_or_admin($user, $p['user_id']), 'hearts' => $n, 'mine' => $mine, 'comments' => $cs,
    )));
}

case 'photo_update': {
    $p = row('SELECT * FROM photos WHERE id = ?', array(post_int('id')));
    if (!$p) fail('Foto nicht gefunden.', 404);
    if (!is_owner_or_admin($user, $p['user_id'])) fail('Du darfst nur deine eigenen Fotos ändern.', 403);
    $album = post_int('album_id');
    if ($album && !val('SELECT COUNT(*) FROM albums WHERE id = ?', array($album))) $album = 0;
    q('UPDATE photos SET caption = ?, album_id = ? WHERE id = ?', array(mb_substr(post_str('caption'), 0, 500), $album ? $album : null, $p['id']));
    ok(array('msg' => 'Gespeichert.'));
}

case 'photo_delete': {
    $p = row('SELECT * FROM photos WHERE id = ?', array(post_int('id')));
    if (!$p) fail('Foto nicht gefunden.', 404);
    if (!is_owner_or_admin($user, $p['user_id'])) fail('Du darfst nur deine eigenen Fotos löschen.', 403);
    image_delete('photos', $p['file']);
    q('DELETE FROM photos WHERE id = ?', array($p['id']));
    q("DELETE FROM comments WHERE target_type = 'photo' AND target_id = ?", array($p['id']));
    q("DELETE FROM reactions WHERE target_type = 'photo' AND target_id = ?", array($p['id']));
    ok(array('msg' => 'Das Foto wurde gelöscht.'));
}

case 'photo_bulk': {
    $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : array();
    $op = post_str('op');
    if (!$ids) fail('Nichts ausgewählt.');
    $done = 0; $skipped = 0;
    $album = post_int('album_id');
    if ($op === 'move' && $album && !val('SELECT COUNT(*) FROM albums WHERE id = ?', array($album))) fail('Album nicht gefunden.', 404);
    foreach ($ids as $id) {
        $p = row('SELECT * FROM photos WHERE id = ?', array($id));
        if (!$p || !is_owner_or_admin($user, $p['user_id'])) { $skipped++; continue; }
        if ($op === 'delete') {
            image_delete('photos', $p['file']);
            q('DELETE FROM photos WHERE id = ?', array($id));
            q("DELETE FROM comments WHERE target_type = 'photo' AND target_id = ?", array($id));
            q("DELETE FROM reactions WHERE target_type = 'photo' AND target_id = ?", array($id));
        } elseif ($op === 'move') {
            q('UPDATE photos SET album_id = ? WHERE id = ?', array($album ? $album : null, $id));
        } else fail('Unbekannte Aktion.');
        $done++;
    }
    if ($skipped) flash($done . ' erledigt, ' . $skipped . ' übersprungen (nicht deine Fotos).', $done ? 'ok' : 'err');
    else flash($done . ($done === 1 ? ' Foto' : ' Fotos') . ($op === 'delete' ? ' gelöscht.' : ' verschoben.'));
    ok(array('reload' => true));
}

case 'album_save': {
    $id = post_int('id'); $title = post_str('title'); $desc = post_str('description');
    if ($title === '' || mb_strlen($title) > 80) fail('Bitte gib einen Titel ein (höchstens 80 Zeichen).');
    if ($id) {
        $a = row('SELECT * FROM albums WHERE id = ?', array($id));
        if (!$a) fail('Album nicht gefunden.', 404);
        if (!is_owner_or_admin($user, $a['created_by'])) fail('Nur wer das Album angelegt hat (oder ein Admin) darf es ändern.', 403);
        q('UPDATE albums SET title = ?, description = ? WHERE id = ?', array($title, mb_substr($desc, 0, 500), $id));
    } else {
        q('INSERT INTO albums (title, description, created_by) VALUES (?, ?, ?)', array($title, mb_substr($desc, 0, 500), $user['id']));
        $id = last_id();
    }
    ok(array('redirect' => 'fotos.php?album=' . $id));
}

case 'album_delete': {
    $a = row('SELECT * FROM albums WHERE id = ?', array(post_int('id')));
    if (!$a) fail('Album nicht gefunden.', 404);
    if (!is_owner_or_admin($user, $a['created_by'])) fail('Nur wer das Album angelegt hat (oder ein Admin) darf es löschen.', 403);
    q('UPDATE photos SET album_id = NULL WHERE album_id = ?', array($a['id']));
    q('DELETE FROM albums WHERE id = ?', array($a['id']));
    flash('Das Album wurde gelöscht. Die Fotos bleiben erhalten.');
    ok(array('redirect' => 'fotos.php'));
}

case 'recipe_fav': {
    $id = post_int('id');
    if (!val('SELECT COUNT(*) FROM recipes WHERE id = ?', array($id))) fail('Rezept nicht gefunden.', 404);
    $has = val('SELECT COUNT(*) FROM recipe_favs WHERE recipe_id = ? AND user_id = ?', array($id, $user['id']));
    if ($has) q('DELETE FROM recipe_favs WHERE recipe_id = ? AND user_id = ?', array($id, $user['id']));
    else q('INSERT INTO recipe_favs (recipe_id, user_id) VALUES (?, ?)', array($id, $user['id']));
    ok(array('fav' => !$has));
}

case 'recipe_rate': {
    $id = post_int('id'); $stars = post_int('stars');
    if (!val('SELECT COUNT(*) FROM recipes WHERE id = ?', array($id))) fail('Rezept nicht gefunden.', 404);
    if ($stars < 0 || $stars > 5) fail('Ungültige Bewertung.');
    if ($stars === 0) q('DELETE FROM recipe_ratings WHERE recipe_id = ? AND user_id = ?', array($id, $user['id']));
    else q('INSERT OR REPLACE INTO recipe_ratings (recipe_id, user_id, stars) VALUES (?, ?, ?)', array($id, $user['id'], $stars));
    $r = row('SELECT AVG(stars) a, COUNT(*) n FROM recipe_ratings WHERE recipe_id = ?', array($id));
    ok(array('avg' => $r['n'] ? round((float)$r['a'], 1) : 0, 'count' => (int)$r['n'], 'mine' => $stars));
}

case 'post_add': {
    $body = post_str('body');
    if ($body === '' || mb_strlen($body) > 2000) fail('Der Beitrag muss zwischen 1 und 2000 Zeichen lang sein.');
    q('INSERT INTO posts (user_id, body) VALUES (?, ?)', array($user['id'], $body));
    ok(array('reload' => true));
}

case 'post_delete': {
    $p = row('SELECT * FROM posts WHERE id = ?', array(post_int('id')));
    if (!$p) fail('Beitrag nicht gefunden.', 404);
    if (!is_owner_or_admin($user, $p['user_id'])) fail('Du darfst nur eigene Beiträge löschen.', 403);
    q('DELETE FROM posts WHERE id = ?', array($p['id']));
    q("DELETE FROM comments WHERE target_type = 'post' AND target_id = ?", array($p['id']));
    q("DELETE FROM reactions WHERE target_type = 'post' AND target_id = ?", array($p['id']));
    ok(array('reload' => true));
}

case 'post_pin': {
    if (!has_role($user, 'editor')) fail('Das dürfen Redakteure und Admins.', 403);
    $p = row('SELECT * FROM posts WHERE id = ?', array(post_int('id')));
    if (!$p) fail('Beitrag nicht gefunden.', 404);
    q('UPDATE posts SET pinned = ? WHERE id = ?', array($p['pinned'] ? 0 : 1, $p['id']));
    ok(array('reload' => true));
}

case 'person_delete': {
    $p = row('SELECT * FROM persons WHERE id = ?', array(post_int('id')));
    if (!$p) fail('Person nicht gefunden.', 404);
    if (!is_owner_or_admin($user, $p['created_by'])) fail('Du darfst nur Personen löschen, die du selbst angelegt hast.', 403);
    image_delete('persons', $p['photo']);
    q('DELETE FROM persons WHERE id = ?', array($p['id']));
    flash('Die Person wurde gelöscht.');
    ok(array('redirect' => 'stammbaum.php'));
}

default:
    fail('Unbekannte Aktion.', 404);
}
