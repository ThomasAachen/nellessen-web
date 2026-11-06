<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/common.php';
session_start_secure();

if (auth_user()) redirect('index.php');
if ((int)val('SELECT COUNT(*) FROM users') === 0) redirect('setup.php');

$error = '';
$next = get_str('next');
if (!preg_match('~^/familie/[A-Za-z0-9_./?=&%-]*$~', $next) || strpos($next, 'login.php') !== false) $next = 'index.php';
else $next = basename(parse_url($next, PHP_URL_PATH)) . (parse_url($next, PHP_URL_QUERY) ? '?' . parse_url($next, PHP_URL_QUERY) : '');

if (is_post()) {
    csrf_check();
    $name = post_str('username');
    if (login_throttled($name)) {
        $error = 'Zu viele Fehlversuche. Bitte warte 15 Minuten.';
    } else {
        $user = login($name, isset($_POST['password']) ? (string)$_POST['password'] : '');
        if ($user) redirect((int)$user['must_change'] === 1 ? 'profil.php?force=1' : $next);
        login_failed($name);
        $error = 'Benutzername oder Passwort falsch.';
        sleep(1);
    }
}

page_head('Anmelden · Familie Nellessen', 'Geschützter Familienbereich');
site_nav('familie', true);
?>
<div style="min-height:100svh;display:flex;align-items:center;justify-content:center;padding:6rem 1.5rem 3rem;background:radial-gradient(ellipse at 50% 0%,rgba(194,245,106,.04) 0%,transparent 65%)">
  <div style="width:100%;max-width:420px">
    <div style="text-align:center;margin-bottom:3rem">
      <p class="eyebrow" style="justify-content:center">Privater Bereich</p>
      <h1 style="font-size:2.5rem;font-weight:700;letter-spacing:-.03em;color:#fff;margin-top:.5rem">Willkommen<br><span style="color:var(--lime)">zurück.</span></h1>
      <p style="font-family:var(--ffi);font-size:.9rem;color:rgba(255,255,255,.35);margin-top:1rem;line-height:1.6">Dieser Bereich ist nur für die Familie.<br>Bitte melde dich an.</p>
    </div>
    <?php foreach (flash_pull() as $f): ?><div class="alert <?= $f[0] === 'err' ? 'alert-err' : 'alert-ok' ?>"><?= h($f[1]) ?></div><?php endforeach ?>
    <?php if ($error): ?><div class="alert alert-err"><?= h($error) ?></div><?php endif ?>
    <form method="post" action="login.php<?= $next !== 'index.php' ? '?next=' . h(urlencode('/familie/' . $next)) : '' ?>" autocomplete="on" style="background:var(--surface);border:1px solid var(--border);border-top:2px solid var(--lime);padding:2.5rem">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="username">Benutzername oder E-Mail</label>
        <input type="text" id="username" name="username" autocomplete="username" value="<?= h(post_str('username')) ?>" required autofocus>
      </div>
      <div class="form-group" style="margin-bottom:2rem">
        <label for="password">Passwort</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-lime" style="width:100%;justify-content:center">Anmelden →</button>
    </form>
    <p style="text-align:center;margin-top:1.5rem;font-family:var(--ffi);font-size:.8rem;color:rgba(255,255,255,.3)">
      Noch kein Zugang? <button type="button" onclick="openM('kontakt')" style="color:var(--sky);font-size:.8rem;font-family:var(--ffi);text-decoration:underline;background:none;border:none;cursor:pointer">Thomas kontaktieren.</button>
    </p>
  </div>
</div>
<?php site_footer(); ?>
