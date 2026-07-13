<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/../includes/common.php';
$user = require_login();
page_head('Familie · Nellessen');
site_nav('familie');
?>

<div style="min-height:100svh;padding:6rem 2.5rem 4rem">
<div class="container">

  <!-- Header -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;margin-bottom:3rem">
    <div>
      <p class="eyebrow">Familien-Bereich</p>
      <h1 style="font-size:clamp(2rem,5vw,3.5rem);font-weight:700;letter-spacing:-.025em;color:#fff;line-height:1">
        Hallo, <span style="color:var(--lime)"><?= h($user['name']) ?>.</span>
      </h1>
    </div>
    <div style="display:flex;align-items:center;gap:1rem">
      <?php if($user['role']==='admin'): ?>
      <a href="admin.php" class="btn btn-outline" style="font-size:.68rem">⚙ Admin</a>
      <?php endif ?>
      <a href="logout.php" class="btn btn-outline" style="font-size:.68rem">Abmelden</a>
    </div>
  </div>

  <!-- Sub Nav -->
  <nav class="fam-nav">
    <a href="index.php" class="active">Übersicht</a>
    <a href="fotos.php">📷 Fotos</a>
    <a href="kalender.php">🎂 Geburtstage</a>
    <a href="rezepte.php">🍽️ Rezepte</a>
    <a href="erinnerungen.php">📖 Erinnerungen</a>
  </nav>

  <!-- Cards -->
  <div class="card-grid">
    <a href="fotos.php" style="text-decoration:none">
      <div class="card">
        <div class="card-icon">📷</div>
        <div class="card-title">Fotos</div>
        <div class="card-desc">Familienfotos, Ausflüge, besondere Momente — das gemeinsame Bildarchiv der Familie.</div>
        <div class="card-meta" style="margin-top:1.5rem">Platzhalter · Bald verfügbar</div>
      </div>
    </a>
    <a href="kalender.php" style="text-decoration:none">
      <div class="card">
        <div class="card-icon">🎂</div>
        <div class="card-title">Geburtstage</div>
        <div class="card-desc">Damit niemand vergessen wird — alle Geburtstage der Familie auf einen Blick.</div>
        <div class="card-meta" style="margin-top:1.5rem">Platzhalter · Bald verfügbar</div>
      </div>
    </a>
    <a href="rezepte.php" style="text-decoration:none">
      <div class="card">
        <div class="card-icon">🍽️</div>
        <div class="card-title">Rezepte</div>
        <div class="card-desc">Die Lieblingsrezepte der Familie — von Omas Weckmännern bis zum Weihnachtsstollen.</div>
        <div class="card-meta" style="margin-top:1.5rem">Platzhalter · Bald verfügbar</div>
      </div>
    </a>
    <a href="erinnerungen.php" style="text-decoration:none">
      <div class="card">
        <div class="card-icon">📖</div>
        <div class="card-title">Erinnerungen</div>
        <div class="card-desc">Geschichten, Anekdoten und Momente, die erhalten bleiben sollen — für uns und die Nächsten.</div>
        <div class="card-meta" style="margin-top:1.5rem">Platzhalter · Bald verfügbar</div>
      </div>
    </a>
  </div>

  <!-- Info Box -->
  <div class="alert alert-info" style="margin-top:2rem">
    ℹ️ &nbsp;Dieser Bereich wird gerade aufgebaut. Inhalte folgen schrittweise. Bei Fragen oder Wünschen: direkt an Thomas.
  </div>

</div>
</div>

<?php site_footer(); ?>
