<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/../includes/common.php';
$user = require_login();
page_head('Rezepte · Familie Nellessen');
site_nav('familie');
?>
<div style="min-height:100svh;padding:6rem 2.5rem 4rem">
<div class="container">
  <a href="index.php" style="font-size:.7rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);display:inline-flex;align-items:center;gap:.5rem;margin-bottom:2.5rem;transition:color .2s" onmouseover="this.style.color='var(--lime)'" onmouseout="this.style.color='var(--muted)'">← Übersicht</a>
  <p class="eyebrow">Familien-Bereich</p>
  <h1 style="font-size:clamp(2.5rem,6vw,4.5rem);font-weight:700;letter-spacing:-.025em;color:#fff;line-height:1;margin-bottom:1.5rem">
    🍽️ <span style="color:var(--lime)">Rezepte</span>
  </h1>
  <div class="alert alert-info" style="max-width:600px">
    <strong style="color:var(--sky)">Bald verfügbar.</strong><br>
    Dieser Bereich wird gerade eingerichtet. Inhalte folgen in Kürze. Bei Wünschen oder Vorschlägen: <a href="mailto:info@nellessen.de" style="color:var(--sky)">info@nellessen.de</a>
  </div>
</div>
</div>
<?php site_footer(); ?>
