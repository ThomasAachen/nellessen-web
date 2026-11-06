<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../includes/mailer.php';
$user = require_role('admin');

function admin_count() { return (int)val("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1"); }

if (is_post()) {
    csrf_check();
    $do = post_str('do');
    $uid = post_int('uid');
    $target = $uid ? row('SELECT * FROM users WHERE id = ?', array($uid)) : null;
    $self = $target && (int)$target['id'] === (int)$user['id'];

    if ($do === 'create') {
        $un = post_str('username'); $name = post_str('name'); $mail = post_str('email'); $role = post_str('role', 'member');
        $pw = post_str('password');
        if ($pw === '') $pw = gen_password(10);
        if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $un)) flash('Der Benutzername darf 3 bis 30 Buchstaben, Ziffern, Punkte, Unter- oder Bindestriche enthalten.', 'err');
        elseif (mb_strlen($name) < 2) flash('Bitte gib einen Namen ein.', 'err');
        elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) flash('Bitte gib eine gültige E-Mail-Adresse ein.', 'err');
        elseif (!in_array($role, array('member', 'editor', 'admin'), true)) flash('Ungültige Rolle.', 'err');
        elseif (strlen($pw) < 8) flash('Das Startpasswort braucht mindestens 8 Zeichen.', 'err');
        elseif (val('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?', array($un, $mail))) flash('Benutzername oder E-Mail gibt es schon.', 'err');
        else {
            q('INSERT INTO users (username, name, email, password, role, active, must_change, ical_token) VALUES (?, ?, ?, ?, ?, 1, 1, ?)',
              array($un, $name, $mail, password_hash($pw, PASSWORD_DEFAULT), $role, new_token()));
            $_SESSION['created_user'] = array('name' => $name, 'username' => $un, 'password' => $pw);
            flash('Der Zugang für ' . $name . ' wurde angelegt.');
        }
    } elseif ($target && $do === 'role') {
        $role = post_str('role');
        if (!in_array($role, array('member', 'editor', 'admin'), true)) flash('Ungültige Rolle.', 'err');
        elseif ($target['role'] === 'admin' && $role !== 'admin' && admin_count() <= 1) flash('Es muss mindestens ein Admin übrig bleiben.', 'err');
        else { q('UPDATE users SET role = ? WHERE id = ?', array($role, $uid)); flash('Rolle von ' . $target['name'] . ' ist jetzt: ' . role_label($role) . '.'); }
    } elseif ($target && $do === 'toggle') {
        if ($self) flash('Du kannst dich nicht selbst sperren.', 'err');
        elseif ($target['role'] === 'admin' && $target['active'] && admin_count() <= 1) flash('Es muss mindestens ein Admin aktiv bleiben.', 'err');
        else { q('UPDATE users SET active = ? WHERE id = ?', array($target['active'] ? 0 : 1, $uid)); flash($target['active'] ? 'Der Zugang wurde gesperrt.' : 'Der Zugang wurde wieder freigegeben.'); }
    } elseif ($target && $do === 'reset') {
        $pw = gen_password(10);
        q('UPDATE users SET password = ?, must_change = 1 WHERE id = ?', array(password_hash($pw, PASSWORD_DEFAULT), $uid));
        $_SESSION['created_user'] = array('name' => $target['name'], 'username' => $target['username'], 'password' => $pw);
        flash('Neues Startpasswort für ' . $target['name'] . ' wurde gesetzt.');
    } elseif ($target && $do === 'delete') {
        $used = (int)val('SELECT (SELECT COUNT(*) FROM photos WHERE user_id = ?) + (SELECT COUNT(*) FROM recipes WHERE user_id = ?) + (SELECT COUNT(*) FROM memories WHERE user_id = ?) + (SELECT COUNT(*) FROM posts WHERE user_id = ?) + (SELECT COUNT(*) FROM comments WHERE user_id = ?)', array($uid, $uid, $uid, $uid, $uid));
        if ($self) flash('Du kannst dich nicht selbst löschen.', 'err');
        elseif ($used > 0) flash('Dieser Nutzer hat Inhalte (Fotos, Rezepte, Geschichten oder Kommentare). Bitte sperre den Zugang, dann bleiben die Inhalte erhalten.', 'err');
        else { q('UPDATE persons SET user_id = NULL WHERE user_id = ?', array($uid)); q('DELETE FROM reactions WHERE user_id = ?', array($uid)); q('DELETE FROM recipe_ratings WHERE user_id = ?', array($uid)); q('DELETE FROM recipe_favs WHERE user_id = ?', array($uid)); q('DELETE FROM users WHERE id = ?', array($uid)); flash('Der Nutzer wurde gelöscht.'); }
    } elseif ($do === 'settings') {
        $port = post_int('smtp_port', 587);
        setting_set('smtp_host', post_str('smtp_host', 'smtp.ionos.de'));
        setting_set('smtp_port', (string)($port > 0 ? $port : 587));
        setting_set('smtp_user', post_str('smtp_user'));
        if (post_str('smtp_pass') !== '') setting_set('smtp_pass', isset($_POST['smtp_pass']) ? (string)$_POST['smtp_pass'] : '');
        setting_set('smtp_from', post_str('smtp_from'));
        setting_set('smtp_from_name', post_str('smtp_from_name', 'Familie Nellessen'));
        setting_set('contact_to', post_str('contact_to'));
        setting_set('reminders_enabled', isset($_POST['reminders_enabled']) ? '1' : '0');
        $days = array();
        foreach (explode(',', post_str('reminder_days')) as $d) { $d = trim($d); if ($d !== '' && ctype_digit($d) && (int)$d <= 60) $days[] = (int)$d; }
        setting_set('reminder_days', $days ? implode(',', array_unique($days)) : '7,1');
        flash('Die Einstellungen wurden gespeichert.');
    } elseif ($do === 'testmail') {
        list($ok, $err) = send_mail($user['email'], 'Testmail von nellessen.de', "Das Mailsystem funktioniert.\n\nDiese Nachricht wurde aus dem Adminbereich gesendet.\n");
        flash($ok ? 'Die Testmail ging an ' . $user['email'] . '.' : 'Versand fehlgeschlagen: ' . $err, $ok ? 'ok' : 'err');
    } elseif ($do === 'remind_now') {
        flash('Erinnerungslauf: ' . run_reminders(true));
    } elseif ($do === 'cron_key') {
        setting_set('cron_key', new_token());
        flash('Es gibt einen neuen Cron-Schlüssel.');
    }
    redirect('admin.php');
}

if (setting_get('cron_key', '') === '') setting_set('cron_key', new_token());
$c = mailer_config();
$users = rows('SELECT * FROM users ORDER BY active DESC, name COLLATE NOCASE');
$created = isset($_SESSION['created_user']) ? $_SESSION['created_user'] : null;
unset($_SESSION['created_user']);
$stats = array(
    'Nutzer' => (int)val('SELECT COUNT(*) FROM users WHERE active = 1'),
    'Fotos' => (int)val('SELECT COUNT(*) FROM photos'),
    'Rezepte' => (int)val('SELECT COUNT(*) FROM recipes'),
    'Geschichten' => (int)val('SELECT COUNT(*) FROM memories'),
    'Personen' => (int)val('SELECT COUNT(*) FROM persons'),
);
$mb = round(((int)val('SELECT COALESCE(SUM(bytes),0) FROM photos')) / 1048576, 1);
$base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';

fam_start('Admin', '', $user);
fam_heading('Verwaltung', '<em>Admin</em>bereich');
if ($created): ?>
<div class="alert alert-info"><strong>Zugangsdaten für <?= h($created['name']) ?>:</strong> Benutzername <code><?= h($created['username']) ?></code>, Startpasswort <code id="newpw"><?= h($created['password']) ?></code>.
  Bitte gib sie persönlich weiter. Sie werden nur jetzt angezeigt, und das Passwort muss bei der ersten Anmeldung geändert werden.</div>
<?php endif ?>
<div class="stat-row">
  <?php foreach ($stats as $l => $n): ?><div class="stat"><div class="n"><?= $n ?></div><div class="l"><?= h($l) ?></div></div><?php endforeach ?>
  <div class="stat"><div class="n"><?= $mb ?> MB</div><div class="l">Fotospeicher</div></div>
</div>

<div class="panel">
  <h2>Nutzer</h2>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Name</th><th>Login</th><th>Rolle</th><th>Letzter Login</th><th>Aktionen</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): $isSelf = (int)$u['id'] === (int)$user['id']; ?>
      <tr<?= $u['active'] ? '' : ' style="opacity:.5"' ?>>
        <td><strong><?= h($u['name']) ?></strong><br><span class="muted tiny"><?= h($u['email']) ?></span></td>
        <td><?= h($u['username']) ?><?= $u['must_change'] ? '<br><span class="tiny muted">Passwort offen</span>' : '' ?></td>
        <td>
          <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="role"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
            <select name="role" onchange="this.form.submit()" style="background:#121612;color:#e8ecf0;border:1px solid var(--border);padding:.35rem .5rem;font-size:.8rem">
              <?php foreach (array('member', 'editor', 'admin') as $r): ?><option value="<?= $r ?>"<?= $u['role'] === $r ? ' selected' : '' ?>><?= h(role_label($r)) ?></option><?php endforeach ?>
            </select></form>
        </td>
        <td class="muted small"><?= $u['last_login'] ? h(fmt_dt($u['last_login'])) : 'noch nie' ?></td>
        <td>
          <form method="post" class="inline-form" data-confirm="Neues Startpasswort setzen? Das alte Passwort wird sofort ungültig."><?= csrf_field() ?><input type="hidden" name="do" value="reset"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>"><button class="link-btn">Passwort zurücksetzen</button></form>
          <?php if (!$isSelf): ?>
           · <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>"><button class="link-btn"><?= $u['active'] ? 'Sperren' : 'Freigeben' ?></button></form>
           · <form method="post" class="inline-form" data-confirm="Nutzer endgültig löschen?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="uid" value="<?= (int)$u['id'] ?>"><button class="link-btn" style="color:#f87171">Löschen</button></form>
          <?php endif ?>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table></div>
  <p class="muted tiny" style="margin-top:1rem"><strong>Mitglied:</strong> eigene Inhalte verwalten. <strong>Redakteur:</strong> zusätzlich Stammbaum und Termine pflegen. <strong>Admin:</strong> alles, inklusive Nutzer und Einstellungen.</p>
</div>

<div class="panel">
  <h2>Neuen Zugang anlegen</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="create">
    <div class="form-3">
      <div class="form-group"><label>Name</label><input name="name" required maxlength="80"></div>
      <div class="form-group"><label>Benutzername</label><input name="username" required pattern="[A-Za-z0-9._-]{3,30}"></div>
      <div class="form-group"><label>E-Mail</label><input type="email" name="email" required></div>
    </div>
    <div class="form-2">
      <div class="form-group"><label>Startpasswort</label><input name="password" minlength="8" placeholder="leer lassen für ein zufälliges Passwort" autocomplete="off"></div>
      <div class="form-group"><label>Rolle</label><select name="role"><option value="member">Mitglied</option><option value="editor">Redakteur</option><option value="admin">Admin</option></select></div>
    </div>
    <button class="btn btn-lime">Zugang anlegen</button>
  </form>
</div>

<div class="panel">
  <h2>Einstellungen</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="settings">
    <p class="sub-h">E-Mail-Versand</p>
    <div class="form-3">
      <div class="form-group"><label>SMTP-Server</label><input name="smtp_host" value="<?= h($c['host']) ?>"></div>
      <div class="form-group"><label>Port</label><input name="smtp_port" type="number" value="<?= (int)$c['port'] ?>"></div>
      <div class="form-group"><label>SMTP-Benutzer</label><input name="smtp_user" value="<?= h($c['user']) ?>"></div>
    </div>
    <div class="form-3">
      <div class="form-group"><label>SMTP-Passwort</label><input type="password" name="smtp_pass" autocomplete="new-password" placeholder="<?= $c['pass'] !== '' ? 'gespeichert, leer lassen zum Behalten' : 'noch nicht hinterlegt' ?>"><div class="hint <?= $c['pass'] !== '' ? 'status-ok' : 'status-bad' ?>"><?= $c['pass'] !== '' ? 'Passwort ist hinterlegt.' : 'Ohne Passwort funktionieren Kontaktformular und Erinnerungen nicht.' ?></div></div>
      <div class="form-group"><label>Absenderadresse</label><input name="smtp_from" value="<?= h($c['from']) ?>"></div>
      <div class="form-group"><label>Absendername</label><input name="smtp_from_name" value="<?= h($c['from_name']) ?>"></div>
    </div>
    <div class="form-group"><label>Kontaktformular geht an</label><input name="contact_to" value="<?= h($c['contact_to']) ?>"></div>
    <p class="sub-h" style="margin-top:1.5rem">Erinnerungen</p>
    <div class="form-2">
      <div class="form-group"><label class="check" style="text-transform:none;letter-spacing:0;font-size:.9rem"><input type="checkbox" name="reminders_enabled" value="1" <?= setting_get('reminders_enabled', '0') === '1' ? 'checked' : '' ?>> Erinnerungsmails aktivieren</label></div>
      <div class="form-group"><label>Tage vorher (Komma getrennt)</label><input name="reminder_days" value="<?= h(setting_get('reminder_days', '7,1')) ?>"><div class="hint">Zum Beispiel 7,1 für eine Woche und einen Tag vorher, 0 für am Tag selbst.</div></div>
    </div>
    <p class="muted small" style="margin-bottom:1rem">Letzter Lauf: <?= h(setting_get('reminder_status', 'noch nie')) ?></p>
    <button class="btn btn-lime">Einstellungen speichern</button>
  </form>
  <div class="row" style="margin-top:1rem">
    <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="testmail"><button class="btn btn-outline btn-sm">Testmail an mich</button></form>
    <form method="post" data-confirm="Erinnerungen jetzt an alle senden?"><?= csrf_field() ?><input type="hidden" name="do" value="remind_now"><button class="btn btn-outline btn-sm">Erinnerungslauf jetzt</button></form>
  </div>
</div>

<div class="panel">
  <h2>Erinnerungen per Cron (optional)</h2>
  <p class="muted small" style="margin-bottom:1rem">Ohne Cron werden die Mails beim ersten Seitenaufruf des Tages verschickt. Wer sie pünktlich möchte, trägt bei IONOS einen täglichen Cronjob mit dieser Adresse ein:</p>
  <div class="copybox"><input id="cronUrl" readonly value="<?= h($base . 'cron.php?key=' . setting_get('cron_key')) ?>"><button type="button" class="btn btn-outline btn-sm" data-copy="#cronUrl">Kopieren</button></div>
  <form method="post" style="margin-top:1rem"><?= csrf_field() ?><input type="hidden" name="do" value="cron_key"><button class="link-btn">Neuen Schlüssel erzeugen</button></form>
</div>

<div class="panel">
  <h2>Sicherheitscheck</h2>
  <p class="muted small" style="margin-bottom:1rem">Die Datenbank und die Uploads dürfen nicht direkt aus dem Internet erreichbar sein.</p>
  <ul id="secList" class="small" style="list-style:none"><li class="muted">wird geprüft …</li></ul>
</div>
<?php
fam_end('<script>
(function(){
  var list=document.getElementById("secList");list.innerHTML="";
  [["data/nellessen.db","Datenbank"],["uploads/photos/","Fotoordner"],["data/","Datenordner"]].forEach(function(t){
    var li=document.createElement("li");li.textContent=t[1]+": prüfe …";list.appendChild(li);
    fetch(t[0],{cache:"no-store",credentials:"omit"}).then(function(r){
      var ok=r.status===403||r.status===404;
      li.className=ok?"status-ok":"status-bad";
      li.textContent=t[1]+": "+(ok?"geschützt (HTTP "+r.status+")":"ACHTUNG, erreichbar (HTTP "+r.status+")");
    }).catch(function(){li.textContent=t[1]+": nicht prüfbar"});
  });
})();
</script>');
