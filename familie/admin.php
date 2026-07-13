<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/../includes/common.php';
$me = require_admin();

$msg = $err = '';

// Add user
if ($_POST['action'] ?? '' === 'add') {
    csrf_check();
    $uname = trim($_POST['username'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';
    $role  = in_array($_POST['role'] ?? '', ['admin','member']) ? $_POST['role'] : 'member';

    if (!$uname || !$name || !$email || !$pw) {
        $err = 'Alle Felder ausfüllen.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Ungültige E-Mail.';
    } elseif (strlen($pw) < 8) {
        $err = 'Passwort mindestens 8 Zeichen.';
    } else {
        try {
            db()->prepare("INSERT INTO users (username,name,email,password,role) VALUES (?,?,?,?,?)")
                ->execute([$uname, $name, $email, password_hash($pw, PASSWORD_BCRYPT), $role]);
            $msg = "Benutzer „{$name}" wurde angelegt.";
        } catch (\PDOException $e) {
            $err = str_contains($e->getMessage(), 'UNIQUE') ? 'Benutzername oder E-Mail bereits vergeben.' : 'Datenbankfehler.';
        }
    }
}

// Toggle active
if ($_POST['action'] ?? '' === 'toggle' && isset($_POST['uid'])) {
    csrf_check();
    $uid = (int)$_POST['uid'];
    if ($uid !== (int)$me['id']) {
        db()->prepare("UPDATE users SET active = CASE WHEN active=1 THEN 0 ELSE 1 END WHERE id=?")->execute([$uid]);
        $msg = 'Status geändert.';
    } else {
        $err = 'Eigenes Konto kann nicht deaktiviert werden.';
    }
}

// Reset password
if ($_POST['action'] ?? '' === 'resetpw' && isset($_POST['uid'])) {
    csrf_check();
    $uid  = (int)$_POST['uid'];
    $newpw = $_POST['newpw'] ?? '';
    if (strlen($newpw) < 8) {
        $err = 'Passwort mindestens 8 Zeichen.';
    } else {
        db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($newpw, PASSWORD_BCRYPT), $uid]);
        $msg = 'Passwort geändert.';
    }
}

$users = db()->query("SELECT * FROM users ORDER BY role DESC, name ASC")->fetchAll();

page_head('Admin · Familie Nellessen');
site_nav('familie');
?>

<div style="min-height:100svh;padding:6rem 2.5rem 4rem">
<div class="container">

  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:3rem">
    <div>
      <p class="eyebrow">Admin</p>
      <h1 style="font-size:2.5rem;font-weight:700;letter-spacing:-.025em;color:#fff">Benutzerverwaltung</h1>
    </div>
    <div style="display:flex;gap:.75rem">
      <a href="index.php" class="btn btn-outline" style="font-size:.68rem">← Übersicht</a>
      <a href="logout.php" class="btn btn-outline" style="font-size:.68rem">Abmelden</a>
    </div>
  </div>

  <?php if ($msg): ?><div class="alert alert-ok" style="margin-bottom:1.5rem">✓ <?= h($msg) ?></div><?php endif ?>
  <?php if ($err): ?><div class="alert alert-err" style="margin-bottom:1.5rem">✗ <?= h($err) ?></div><?php endif ?>

  <!-- User list -->
  <div style="background:var(--surface);border:1px solid var(--border);margin-bottom:3rem">
    <div style="padding:1.5rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <h2 style="font-size:1rem;font-weight:700;color:#fff">Benutzer (<?= count($users) ?>)</h2>
    </div>
    <div style="overflow-x:auto">
      <table class="tbl">
        <thead><tr><th>Name</th><th>Benutzername</th><th>E-Mail</th><th>Rolle</th><th>Status</th><th>Letzter Login</th><th>Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td style="color:#fff;font-weight:500"><?= h($u['name']) ?></td>
          <td><?= h($u['username']) ?></td>
          <td><?= h($u['email']) ?></td>
          <td><span class="badge <?= $u['role']==='admin'?'badge-lime':'badge-sky' ?>"><?= h($u['role']) ?></span></td>
          <td><span class="badge <?= $u['active']?'badge-lime':'badge-red' ?>"><?= $u['active']?'aktiv':'gesperrt' ?></span></td>
          <td><?= $u['last_login'] ? h(date('d.m.Y H:i', strtotime($u['last_login']))) : '—' ?></td>
          <td>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
              <?php if ((int)$u['id'] !== (int)$me['id']): ?>
              <form method="post" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <button type="submit" class="btn btn-outline" style="font-size:.62rem;padding:.4rem .8rem">
                  <?= $u['active'] ? 'Sperren' : 'Freischalten' ?>
                </button>
              </form>
              <?php endif ?>
              <button onclick="document.getElementById('rpw-<?=$u['id']?>').style.display='block'" class="btn btn-outline" style="font-size:.62rem;padding:.4rem .8rem">PW ändern</button>
            </div>
            <div id="rpw-<?=$u['id']?>" style="display:none;margin-top:.5rem">
              <form method="post" style="display:flex;gap:.5rem">
                <input type="hidden" name="action" value="resetpw">
                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="password" name="newpw" placeholder="Neues Passwort" minlength="8" style="flex:1;padding:.4rem .6rem;font-size:.8rem;background:var(--bg);border:1px solid var(--border);color:var(--text)">
                <button type="submit" class="btn btn-lime" style="font-size:.65rem;padding:.4rem .8rem">OK</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add user -->
  <div style="background:var(--surface);border:1px solid var(--border);border-top:2px solid var(--lime);padding:2.5rem;max-width:600px">
    <h2 style="font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:2rem">Neuen Benutzer anlegen</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group"><label>Vollständiger Name</label><input type="text" name="name" placeholder="Max Mustermann" required></div>
        <div class="form-group"><label>Benutzername</label><input type="text" name="username" placeholder="max.mustermann" required></div>
        <div class="form-group"><label>E-Mail</label><input type="email" name="email" placeholder="max@example.de" required></div>
        <div class="form-group"><label>Passwort</label><input type="password" name="password" placeholder="Min. 8 Zeichen" minlength="8" required></div>
      </div>
      <div class="form-group">
        <label>Rolle</label>
        <select name="role" style="background:rgba(255,255,255,.04);border:1px solid var(--border);padding:.85rem 1rem;color:var(--text)">
          <option value="member">Mitglied</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <button type="submit" class="btn btn-lime">Benutzer anlegen</button>
    </form>
  </div>

</div>
</div>

<?php site_footer(); ?>
