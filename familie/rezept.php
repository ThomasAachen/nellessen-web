<?php
require_once __DIR__ . '/recipes_lib.php';
$user = require_login();
$r = row('SELECT * FROM recipes WHERE id = ?', array(get_int('id')));
if (!$r) { flash('Dieses Rezept gibt es nicht.', 'err'); redirect('rezepte.php'); }

if (is_post()) {
    csrf_check();
    if (post_str('do') === 'delete' && is_owner_or_admin($user, $r['user_id'])) {
        image_delete('recipes', $r['image']);
        q("DELETE FROM comments WHERE target_type = 'recipe' AND target_id = ?", array($r['id']));
        q("DELETE FROM reactions WHERE target_type = 'recipe' AND target_id = ?", array($r['id']));
        q('DELETE FROM recipes WHERE id = ?', array($r['id']));
        flash('Das Rezept wurde gelöscht.');
        redirect('rezepte.php');
    }
    redirect('rezept.php?id=' . (int)$r['id']);
}

$ings = rows('SELECT * FROM recipe_ingredients WHERE recipe_id = ? ORDER BY pos, id', array($r['id']));
list($avg, $nrat) = recipe_rating_info($r['id']);
$myStars = (int)val('SELECT stars FROM recipe_ratings WHERE recipe_id = ? AND user_id = ?', array($r['id'], $user['id']));
$isFav = (int)val('SELECT COUNT(*) FROM recipe_favs WHERE recipe_id = ? AND user_id = ?', array($r['id'], $user['id'])) > 0;
list($hn, $hm) = reaction_info('recipe', $r['id'], $user['id']);
$steps = preg_split('/\n+/', trim(str_replace("\r", '', (string)$r['steps'])));
$steps = array_values(array_filter(array_map('trim', $steps), 'strlen'));
$can = is_owner_or_admin($user, $r['user_id']);

fam_start($r['title'], 'rezepte', $user);
?>
<p class="no-print" style="margin-bottom:1rem"><a href="rezepte.php" class="muted small">← Alle Rezepte</a></p>
<div class="rec-hero print-main">
  <div>
    <?php if ($r['category']): ?><p class="eyebrow"><?= h($r['category']) ?></p><?php endif ?>
    <h1 class="page-title" style="font-size:clamp(1.8rem,4vw,2.8rem)"><?= h($r['title']) ?></h1>
    <?php if ($r['description']): ?><p style="font-family:var(--ffi);color:rgba(255,255,255,.6);margin:1rem 0;line-height:1.7"><?= nl2br(h($r['description'])) ?></p><?php endif ?>
    <div class="rec-meta">
      <?php if ($r['prep_min']): ?><div>Vorbereiten<b><?= (int)$r['prep_min'] ?> Min.</b></div><?php endif ?>
      <?php if ($r['cook_min']): ?><div>Garen / Backen<b><?= (int)$r['cook_min'] ?> Min.</b></div><?php endif ?>
      <div>Rezept von<b><?= h(user_name($r['user_id'])) ?></b></div>
    </div>
    <div class="row no-print">
      <button type="button" class="btn btn-outline btn-sm" id="favBtn" data-id="<?= (int)$r['id'] ?>"><?= $isFav ? '★ Favorit' : '☆ Merken' ?></button>
      <?= reaction_button('recipe', $r['id'], $hn, $hm) ?>
      <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">Drucken</button>
      <?php if ($can): ?><a class="btn btn-outline btn-sm" href="rezept_edit.php?id=<?= (int)$r['id'] ?>">Bearbeiten</a>
      <form method="post" class="inline-form" data-confirm="Rezept endgültig löschen?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger btn-sm">Löschen</button></form><?php endif ?>
    </div>
    <div class="row no-print" style="margin-top:1rem"><span class="muted small">Deine Bewertung:</span>
      <span class="stars" id="myStars" data-id="<?= (int)$r['id'] ?>"><?php for ($i = 1; $i <= 5; $i++): ?><button type="button" data-s="<?= $i ?>" class="<?= $myStars >= $i ? 'on' : '' ?>" aria-label="<?= $i ?> Sterne">★</button><?php endfor ?></span>
      <span class="muted small" id="avgTxt"><?= $nrat ? 'Ø ' . str_replace('.', ',', (string)$avg) . ' (' . $nrat . ')' : 'noch keine Bewertung' ?></span></div>
  </div>
  <?php if ($r['image']): ?><div class="pic"><img src="<?= h(img_url('recipes', $r['image'])) ?>" alt="<?= h($r['title']) ?>"></div><?php endif ?>
</div>

<div class="grid-main print-main">
  <div class="panel">
    <h2>Zutaten</h2>
    <div class="persons"><span class="muted small">Für</span>
      <div class="stepper"><button type="button" id="pMinus" aria-label="Weniger">−</button><input id="persons" type="number" min="1" max="200" value="<?= (int)$r['servings'] ?>" aria-label="Anzahl Personen"><button type="button" id="pPlus" aria-label="Mehr">+</button></div>
      <span class="muted small">Personen</span>
      <button type="button" class="link-btn no-print" id="pReset">zurücksetzen (<?= (int)$r['servings'] ?>)</button></div>
    <ul class="ing-list" id="ingList" data-base="<?= (int)$r['servings'] ?>">
      <?php foreach ($ings as $i): ?>
      <li data-amt="<?= $i['amount'] !== null ? h((string)$i['amount']) : '' ?>" data-unit="<?= h($i['unit']) ?>"><span class="a"></span><span class="n"><?= h($i['name']) ?></span></li>
      <?php endforeach ?>
    </ul>
    <?php if (!$ings): ?><p class="muted small">Keine Zutaten eingetragen.</p><?php endif ?>
    <button type="button" class="btn btn-outline btn-sm no-print" data-open="shopOverlay">Einkaufsliste</button>
  </div>
  <div>
    <div class="panel">
      <h2>Zubereitung</h2>
      <?php if ($steps): ?><ol class="steps"><?php foreach ($steps as $s): ?><li><?= h($s) ?></li><?php endforeach ?></ol><?php else: ?><p class="muted small">Keine Schritte eingetragen.</p><?php endif ?>
    </div>
    <?php if ($r['note']): ?><div class="panel"><h2>Tipp</h2><div class="story" style="font-size:.95rem"><?= rich_text($r['note']) ?></div></div><?php endif ?>
    <?php if ($r['source']): ?><p class="muted small" style="margin-top:1rem">Quelle: <?= preg_match('~^https?://~', $r['source']) ? '<a href="' . h($r['source']) . '" target="_blank" rel="noopener nofollow">' . h($r['source']) . '</a>' : h($r['source']) ?></p><?php endif ?>
  </div>
</div>
<?php comments_block('recipe', $r['id'], $user); ?>

<div class="overlay" id="shopOverlay"><div class="modal">
  <button class="modal-close no-print" data-close="shopOverlay" aria-label="Schließen">×</button>
  <h2>Einkaufsliste</h2>
  <p class="muted small no-print" id="shopFor"></p>
  <div id="shopList"></div>
  <div class="row no-print" style="margin-top:1.2rem"><button type="button" class="btn btn-outline btn-sm" id="shopCopy">Kopieren</button>
    <button type="button" class="btn btn-outline btn-sm" id="shopPrint">Drucken</button></div>
</div></div>
<?php
fam_end('<script src="rezept.js?v=' . (int)@filemtime(__DIR__ . '/rezept.js') . '"></script>');
