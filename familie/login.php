<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/../includes/common.php';

// Already logged in?
if (auth_user()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
    if ($user) {
        $next = preg_match('#^/familie/#', $_GET['next'] ?? '') ? $_GET['next'] : 'index.php';
        header('Location: ' . $next); exit;
    }
    $error = 'Benutzername oder Passwort falsch.';
    sleep(1); // slow brute-force
}

page_head('Login · Familie Nellessen');
site_nav('familie');
?>

<div style="min-height:100svh;display:flex;align-items:center;justify-content:center;padding:6rem 1.5rem 3rem;background:radial-gradient(ellipse at 50% 0%,rgba(194,245,106,.04) 0%,transparent 65%)">
  <div style="width:100%;max-width:420px">

    <div style="text-align:center;margin-bottom:3rem">
      <p class="eyebrow" style="justify-content:center">Privater Bereich</p>
      <h1 style="font-size:2.5rem;font-weight:700;letter-spacing:-.03em;color:#fff;margin-top:.5rem">Willkommen<br><span style="color:var(--lime)">zurück.</span></h1>
      <p style="font-family:var(--ffi);font-size:.9rem;color:rgba(255,255,255,.35);margin-top:1rem;line-height:1.6">Dieser Bereich ist nur für Familie.<br>Bitte melde dich an.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-err" style="margin-bottom:1.5rem">✗ <?= h($error) ?></div>
    <?php endif ?>

    <form method="post" action="" autocomplete="on" style="background:var(--surface);border:1px solid var(--border);border-top:2px solid var(--lime);padding:2.5rem">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label for="username">Benutzername</label>
        <input type="text" id="username" name="username" autocomplete="username"
               value="<?= h($_POST['username'] ?? '') ?>" required autofocus
               placeholder="dein.name">
      </div>
      <div class="form-group" style="margin-bottom:2rem">
        <label for="password">Passwort</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-lime" style="width:100%;justify-content:center">
        Anmelden →
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;font-family:var(--ffi);font-size:.8rem;color:rgba(255,255,255,.2)">
      Noch kein Zugang? <button onclick="openM('kontakt')" style="color:var(--sky);font-size:.8rem;font-family:var(--ffi);text-decoration:underline;background:none;border:none;cursor:pointer">Thomas kontaktieren.</button>
    </p>

  </div>
</div>

<?php site_footer(); ?>
