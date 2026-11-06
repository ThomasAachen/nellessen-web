<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$today = today_ymd();
$upcoming = array_slice(array_values(array_filter(family_dates($today, date('Y-m-d', strtotime($today . ' +60 days'))), function ($d) { return $d['kind'] !== 'memorial'; })), 0, 6);
$latestPhotos = rows('SELECT id, file, user_id FROM photos ORDER BY id DESC LIMIT 12');
$posts = rows('SELECT p.*, (SELECT COUNT(*) FROM reactions r WHERE r.target_type=\'post\' AND r.target_id=p.id) hearts,
               (SELECT COUNT(*) FROM reactions r WHERE r.target_type=\'post\' AND r.target_id=p.id AND r.user_id=?) mine
               FROM posts p ORDER BY p.pinned DESC, p.id DESC LIMIT 15', array($user['id']));
$memories = rows('SELECT id, title, user_id, year FROM memories ORDER BY id DESC LIMIT 5');
$recipes = rows('SELECT id, title, user_id FROM recipes ORDER BY id DESC LIMIT 5');
$n = array(
    array('fotos.php', (int)val('SELECT COUNT(*) FROM photos'), 'Fotos'),
    array('rezepte.php', (int)val('SELECT COUNT(*) FROM recipes'), 'Rezepte'),
    array('erinnerungen.php', (int)val('SELECT COUNT(*) FROM memories'), 'Erinnerungen'),
    array('stammbaum.php', (int)val('SELECT COUNT(*) FROM persons'), 'Personen im Stammbaum'),
);
$canPin = has_role($user, 'editor');

fam_start('Übersicht', 'index', $user);
$hour = (int)date('G');
$greet = $hour < 11 ? 'Guten Morgen' : ($hour < 18 ? 'Hallo' : 'Guten Abend');
fam_heading('Familienbereich', h($greet) . ', <em>' . h(explode(' ', $user['name'])[0]) . '</em>');
?>
<div class="stat-row">
  <?php foreach ($n as $s): ?><a class="stat" href="<?= $s[0] ?>"><div class="n"><?= $s[1] ?></div><div class="l"><?= h($s[2]) ?></div></a><?php endforeach ?>
</div>

<div class="grid-main">
  <div>
    <div class="panel">
      <h2>Pinnwand</h2>
      <form data-api="post_add" class="comment-form" style="margin:0 0 1rem">
        <textarea name="body" rows="2" maxlength="2000" required placeholder="Was gibt es Neues? Ein Gruß, eine Frage, eine Einladung …"></textarea>
        <button class="btn btn-lime btn-sm" type="submit">Posten</button>
      </form>
      <?php if (!$posts): ?><div class="empty">Noch nichts an der Pinnwand. Schreib den ersten Gruß.</div><?php endif ?>
      <?php foreach ($posts as $p): ?>
      <div class="post<?= $p['pinned'] ? ' pinned' : '' ?>">
        <div class="row" style="gap:.6rem"><?= avatar_html(user_name($p['user_id']), 30) ?>
          <strong style="color:#fff;font-size:.85rem"><?= h(user_name($p['user_id'])) ?></strong>
          <span class="muted small"><?= h(rel_time($p['created_at'])) ?></span><?= $p['pinned'] ? '<span class="tag">angeheftet</span>' : '' ?></div>
        <div class="p-text"><?= rich_text($p['body']) ?></div>
        <div class="row"><?= reaction_button('post', $p['id'], $p['hearts'], (int)$p['mine'] > 0) ?>
          <?php if ($canPin): ?><button type="button" class="link-btn" data-api-click="post_pin" data-id="<?= (int)$p['id'] ?>"><?= $p['pinned'] ? 'lösen' : 'anheften' ?></button><?php endif ?>
          <?php if (is_owner_or_admin($user, $p['user_id'])): ?><button type="button" class="link-btn" style="color:#f87171" data-api-click="post_delete" data-id="<?= (int)$p['id'] ?>" data-confirm="Beitrag löschen?">löschen</button><?php endif ?></div>
      </div>
      <?php endforeach ?>
    </div>
    <?php if ($latestPhotos): ?>
    <div class="panel"><div class="row between"><h2 style="margin:0">Neue Fotos</h2><a href="fotos.php" class="link-btn">alle ansehen</a></div>
      <div class="strip" style="margin-top:1rem"><?php foreach ($latestPhotos as $p): ?><a href="fotos.php?open=<?= (int)$p['id'] ?>"><img src="<?= h(img_url('photos', $p['file'], true)) ?>" alt="" loading="lazy"></a><?php endforeach ?></div></div>
    <?php endif ?>
  </div>
  <div>
    <div class="panel"><div class="row between"><h2 style="margin:0">Als Nächstes</h2><a href="termine.php" class="link-btn">Kalender</a></div>
      <div class="mini-list" style="margin-top:.8rem">
      <?php if (!$upcoming): ?><p class="muted small">In den nächsten 60 Tagen steht nichts an.</p><?php endif ?>
      <?php foreach ($upcoming as $d): ?><a href="<?= h($d['link']) ?>"><span><?= h($d['title']) ?><br><span class="muted tiny"><?= h(de_date($d['date'])) ?></span></span><span class="lime tiny" style="white-space:nowrap"><?= h(when_text($d['date'])) ?></span></a><?php endforeach ?>
      </div></div>
    <div class="panel"><div class="row between"><h2 style="margin:0">Neue Geschichten</h2><a href="erinnerungen.php" class="link-btn">alle</a></div>
      <div class="mini-list" style="margin-top:.8rem">
      <?php if (!$memories): ?><p class="muted small">Noch keine Geschichten.</p><?php endif ?>
      <?php foreach ($memories as $m): ?><a href="erinnerung.php?id=<?= (int)$m['id'] ?>"><span><?= h($m['title']) ?></span><span class="muted tiny"><?= h(user_name($m['user_id'])) ?></span></a><?php endforeach ?>
      </div></div>
    <div class="panel"><div class="row between"><h2 style="margin:0">Neue Rezepte</h2><a href="rezepte.php" class="link-btn">alle</a></div>
      <div class="mini-list" style="margin-top:.8rem">
      <?php if (!$recipes): ?><p class="muted small">Noch keine Rezepte.</p><?php endif ?>
      <?php foreach ($recipes as $r): ?><a href="rezept.php?id=<?= (int)$r['id'] ?>"><span><?= h($r['title']) ?></span><span class="muted tiny"><?= h(user_name($r['user_id'])) ?></span></a><?php endforeach ?>
      </div></div>
  </div>
</div>
<?php fam_end();
