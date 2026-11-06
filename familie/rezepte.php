<?php
require_once __DIR__ . '/recipes_lib.php';
$user = require_login();

$cat = get_str('cat'); $search = get_str('q'); $fav = get_str('fav') === '1'; $mine = get_str('mine') === '1';
$sort = get_str('sort', 'new');
$where = array(); $par = array();
if ($cat !== '') { $where[] = 'r.category = ?'; $par[] = $cat; }
if ($mine) { $where[] = 'r.user_id = ?'; $par[] = $user['id']; }
if ($fav) { $where[] = 'EXISTS (SELECT 1 FROM recipe_favs f WHERE f.recipe_id = r.id AND f.user_id = ?)'; $par[] = $user['id']; }
if ($search !== '') { $where[] = '(r.title LIKE ? OR r.description LIKE ? OR EXISTS (SELECT 1 FROM recipe_ingredients i WHERE i.recipe_id = r.id AND i.name LIKE ?))'; $like = '%' . $search . '%'; array_push($par, $like, $like, $like); }
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$order = $sort === 'az' ? 'r.title COLLATE NOCASE ASC' : ($sort === 'rated' ? 'avg DESC, r.id DESC' : 'r.id DESC');
$list = rows("SELECT r.*, (SELECT AVG(stars) FROM recipe_ratings x WHERE x.recipe_id = r.id) avg,
              (SELECT COUNT(*) FROM recipe_favs f WHERE f.recipe_id = r.id AND f.user_id = " . (int)$user['id'] . ") isfav
              FROM recipes r $W ORDER BY $order", $par);
$total = (int)val('SELECT COUNT(*) FROM recipes');

fam_start('Rezepte', 'rezepte', $user);
fam_heading('Aus Omas Küche und von heute', '<em>Rezepte</em>', '<a class="btn btn-lime btn-sm" href="rezept_edit.php">Rezept anlegen</a>');
?>
<form class="toolbar no-print" method="get">
  <input type="search" name="q" value="<?= h($search) ?>" placeholder="Rezept oder Zutat suchen …">
  <select name="cat" onchange="this.form.submit()"><option value="">Alle Kategorien</option>
    <?php foreach (recipe_categories() as $c): ?><option<?= $cat === $c ? ' selected' : '' ?>><?= h($c) ?></option><?php endforeach ?></select>
  <select name="sort" onchange="this.form.submit()"><option value="new">Neueste zuerst</option><option value="az"<?= $sort === 'az' ? ' selected' : '' ?>>A bis Z</option><option value="rated"<?= $sort === 'rated' ? ' selected' : '' ?>>Beste Bewertung</option></select>
  <label class="check"><input type="checkbox" name="fav" value="1" onchange="this.form.submit()"<?= $fav ? ' checked' : '' ?>> Favoriten</label>
  <label class="check"><input type="checkbox" name="mine" value="1" onchange="this.form.submit()"<?= $mine ? ' checked' : '' ?>> Von mir</label>
  <button class="btn btn-outline btn-sm">Suchen</button>
</form>
<?php if (!$list): ?>
  <div class="empty"><?= $total ? 'Dazu gibt es kein Rezept. Probier eine andere Suche.' : 'Noch keine Rezepte. Das erste fehlt noch, vielleicht Omas Apfelkuchen?' ?></div>
<?php else: ?>
<div class="rec-grid">
<?php foreach ($list as $r): ?>
  <a class="rec-card" href="rezept.php?id=<?= (int)$r['id'] ?>">
    <div class="img"><?php if ($r['image']): ?><img src="<?= h(img_url('recipes', $r['image'], true)) ?>" alt="" loading="lazy"><?php else: ?>🍲<?php endif ?></div>
    <div class="b"><?php if ($r['category']): ?><div class="cat"><?= h($r['category']) ?></div><?php endif ?>
      <div class="t"><?= $r['isfav'] ? '<span style="color:#fbbf24">★</span> ' : '' ?><?= h($r['title']) ?></div>
      <div class="m"><span><?= (int)$r['servings'] ?> Portionen</span>
        <?php if ($r['prep_min'] || $r['cook_min']): ?><span><?= (int)$r['prep_min'] + (int)$r['cook_min'] ?> Min.</span><?php endif ?>
        <?php if ($r['avg']): ?><span><?= stars_static((float)$r['avg']) ?></span><?php endif ?></div>
      <div class="m"><span>von <?= h(user_name($r['user_id'])) ?></span></div></div>
  </a>
<?php endforeach ?>
</div>
<?php endif;
fam_end();
