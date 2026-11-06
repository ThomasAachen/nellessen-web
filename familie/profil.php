<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$force = (int)$user['must_change'] === 1;

if (is_post()) {
    csrf_check();
    $do = post_str('do');
    if ($do === 'password') {
        $old = isset($_POST['old']) ? (string)$_POST['old'] : '';
        $new = isset($_POST['new']) ? (string)$_POST['new'] : '';
        $rep = isset($_POST['rep']) ? (string)$_POST['rep'] : '';
        if (!password_verify($old, $user['password'])) flash('Das aktuelle Passwort stimmt nicht.', 'err');
        elseif (strlen($new) < 10) flash('Das neue Passwort braucht mindestens 10 Zeichen.', 'err');
        elseif ($new !== $rep) flash('Die beiden neuen Passwörter sind nicht gleich.', 'err');
        elseif ($new === LEGACY_DEFAULT_PW || $new === $old) flash('Bitte wähle ein anderes Passwort als das bisherige.', 'err');
        else {
            q('UPDATE users SET password = ?, must_change = 0 WHERE id = ?', array(password_hash($new, PASSWORD_DEFAULT), $user['id']));
            session_regenerate_id(true);
            flash('Dein Passwort wurde geändert.');
            redirect('index.php');
        }
        redirect('profil.php' . ($force ? '?force=1' : ''));
    }
    if ($force) redirect('profil.php?force=1');
    if ($do === 'data') {
        $name = post_str('name'); $mail = post_str('email');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) flash('Bitte gib einen Namen ein.', 'err');
        elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) flash('Bitte gib eine gültige E-Mail-Adresse ein.', 'err');
        elseif (val('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', array($mail, $user['id']))) flash('Diese E-Mail-Adresse wird schon verwendet.', 'err');
        else {
            q('UPDATE users SET name = ?, email = ?, notify = ? WHERE id = ?', array($name, $mail, isset($_POST['notify']) ? 1 : 0, $user['id']));
            flash('Gespeichert.');
        }
        redirect('profil.php');
    }
    if ($do === 'ical_new') {
        q('UPDATE users SET ical_token = ? WHERE id = ?', array(new_token(), $user['id']));
        flash('Es gibt einen neuen Kalenderlink. Der alte funktioniert nicht mehr.');
        redirect('profil.php');
    }
    redirect('profil.php');
}

$base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
$ical = $base . 'ical.php?t=' . $user['ical_token'];

fam_start('Profil', '', $user, array('narrow' => true, 'bare' => $force));
fam_heading($force ? 'Willkommen' : 'Mein Konto', $force ? 'Neues <em>Passwort</em> wählen' : 'Mein <em>Profil</em>');
if ($force): ?>
<div class="alert alert-info">Bevor es losgeht, brauchst du ein eigenes Passwort. Das Startpasswort ist nur für die erste Anmeldung gedacht.</div>
<?php endif ?>
<div class="panel">
  <h2>Passwort ändern</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="password">
    <div class="form-group"><label>Aktuelles Passwort</label><input type="password" name="old" required autocomplete="current-password"></div>
    <div class="form-2">
      <div class="form-group"><label>Neues Passwort</label><input type="password" name="new" required minlength="10" autocomplete="new-password"><div class="hint">Mindestens 10 Zeichen.</div></div>
      <div class="form-group"><label>Wiederholen</label><input type="password" name="rep" required minlength="10" autocomplete="new-password"></div>
    </div>
    <button class="btn btn-lime">Passwort speichern</button>
  </form>
</div>
<?php if (!$force): ?>
<div class="panel">
  <h2>Meine Daten</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="data">
    <div class="form-2">
      <div class="form-group"><label>Name</label><input name="name" required maxlength="80" value="<?= h($user['name']) ?>"></div>
      <div class="form-group"><label>E-Mail</label><input type="email" name="email" required value="<?= h($user['email']) ?>"></div>
    </div>
    <div class="form-group"><label class="check" style="text-transform:none;letter-spacing:0;font-size:.9rem"><input type="checkbox" name="notify" value="1" <?= $user['notify'] ? 'checked' : '' ?>> Per E-Mail an Geburtstage und Termine erinnern</label></div>
    <p class="muted small" style="margin-bottom:1rem">Benutzername: <strong><?= h($user['username']) ?></strong> · Rolle: <strong><?= h(role_label($user['role'])) ?></strong></p>
    <button class="btn btn-lime">Speichern</button>
  </form>
</div>
<div class="panel">
  <h2>Kalender abonnieren</h2>
  <p class="muted small" style="margin-bottom:1rem">Mit diesem Link zeigt dein Handy- oder Computerkalender (Apple, Google, Outlook) alle Familientermine automatisch an. Den Link bitte nicht weitergeben.</p>
  <div class="copybox"><input id="icalUrl" readonly value="<?= h($ical) ?>"><button type="button" class="btn btn-outline btn-sm" data-copy="#icalUrl">Kopieren</button></div>
  <form method="post" style="margin-top:1rem" data-confirm="Neuen Link erzeugen? Der bisherige Link wird ungültig."><?= csrf_field() ?><input type="hidden" name="do" value="ical_new"><button class="link-btn">Neuen Link erzeugen</button></form>
</div>
<?php endif;
fam_end();
