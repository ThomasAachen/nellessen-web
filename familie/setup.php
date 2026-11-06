<?php
// Einmalige Einrichtung des ersten Administrators. Funktioniert nur, solange es noch keine Nutzer gibt,
// und nur mit dem Einrichtungscode (im Repository steht nur der Hash).
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/common.php';
session_start_secure();

const SETUP_CODE_HASH = '$2y$10$0be0K/nH.zwUN2hk6Z/Ilu.ezUjioET3FXvosyNALNhAdA6Irh0/2';

if ((int)val('SELECT COUNT(*) FROM users') > 0) redirect('login.php');

$error = '';
if (is_post()) {
    csrf_check();
    $code = post_str('code'); $user = post_str('username'); $name = post_str('name'); $mail = post_str('email');
    $pw = isset($_POST['password']) ? (string)$_POST['password'] : '';
    if (login_throttled('setup')) $error = 'Zu viele Versuche. Bitte warte 15 Minuten.';
    elseif (!password_verify($code, SETUP_CODE_HASH)) { login_failed('setup'); $error = 'Der Einrichtungscode stimmt nicht.'; }
    elseif (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $user)) $error = 'Der Benutzername darf 3 bis 30 Buchstaben, Ziffern, Punkte oder Bindestriche enthalten.';
    elseif (mb_strlen($name) < 2) $error = 'Bitte gib einen Namen ein.';
    elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) $error = 'Bitte gib eine gültige E-Mail-Adresse ein.';
    elseif (strlen($pw) < 10) $error = 'Das Passwort braucht mindestens 10 Zeichen.';
    else {
        q("INSERT INTO users (username, name, email, password, role, active, must_change, ical_token) VALUES (?, ?, ?, ?, 'admin', 1, 0, ?)",
          array($user, $name, $mail, password_hash($pw, PASSWORD_DEFAULT), new_token()));
        flash('Der Administrator wurde angelegt. Du kannst dich jetzt anmelden.');
        redirect('login.php');
    }
}
page_head('Einrichtung · Familie Nellessen', 'Einrichtung');
site_nav('familie', true);
?>
<div style="min-height:100svh;display:flex;align-items:center;justify-content:center;padding:6rem 1.5rem 3rem">
  <div style="width:100%;max-width:460px">
    <p class="eyebrow">Einrichtung</p>
    <h1 style="font-size:2.2rem;font-weight:700;color:#fff;margin:.5rem 0 1.5rem">Ersten Admin <span style="color:var(--lime)">anlegen</span></h1>
    <?php if ($error): ?><div class="alert alert-err"><?= h($error) ?></div><?php endif ?>
    <form method="post" style="background:var(--surface);border:1px solid var(--border);border-top:2px solid var(--lime);padding:2rem">
      <?= csrf_field() ?>
      <div class="form-group"><label>Einrichtungscode</label><input type="password" name="code" required autocomplete="off"></div>
      <div class="form-group"><label>Benutzername</label><input name="username" required value="<?= h(post_str('username')) ?>"></div>
      <div class="form-group"><label>Anzeigename</label><input name="name" required value="<?= h(post_str('name')) ?>"></div>
      <div class="form-group"><label>E-Mail</label><input type="email" name="email" required value="<?= h(post_str('email')) ?>"></div>
      <div class="form-group"><label>Passwort (mindestens 10 Zeichen)</label><input type="password" name="password" required autocomplete="new-password"></div>
      <button class="btn btn-lime" style="width:100%;justify-content:center">Anlegen →</button>
    </form>
  </div>
</div>
<?php site_footer(); ?>
