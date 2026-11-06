<?php
require_once __DIR__ . '/layout.php';
$user = require_login();

function memory_kinds() { return array('story' => 'Geschichte', 'anecdote' => 'Anekdote', 'tradition' => 'Tradition', 'milestone' => 'Meilenstein'); }

$kindF = get_str('kind'); $personF = get_int('person'); $search = get_str('q'); $mine = get_str('mine') === '1';
$where = array(); $par = array();
if (isset(memory_kinds()[$kindF])) { $where[] = 'm.kind = ?'; $par[] = $kindF; }
if ($personF) { $where[] = 'EXISTS (SELECT 1 FROM memory_people mp WHERE mp.memory_id = m.id AND mp.person_id = ?)'; $par[] = $personF; }
if ($mine) { $where[] = 'm.user_id = ?'; $par[] = $user['id']; }
if ($search !== '') { $where[] = '(m.title LIKE ? OR m.body LIKE ?)'; $par[] = '%' . $search . '%'; $par[] = '%' . $search . '%'; }
$W = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$list = rows("SELECT m.*, (SELECT COUNT(*) FROM reactions r WHERE r.target_type='memory' AND r.target_id=m.id) hearts,
              (SELECT COUNT(*) FROM comments c WHERE c.target_type='memory' AND c.target_id=m.id) ncom
              FROM memories m $W ORDER BY (m.year IS NULL), m.year DESC, m.id DESC", $par);
$tags = array();
foreach (rows('SELECT mp.memory_id, p.id, p.first_name, p.last_name FROM memory_people mp JOIN persons p ON p.id = mp.person_id') as $t) {
    $tags[(int)$t['memory_id']][] = $t;
}
$persons = rows('SELECT DISTINCT p.id, p.first_name, p.last_name FROM persons p JOIN memory_people mp ON mp.person_id = p.id ORDER BY p.first_name COLLATE NOCASE');
$kinds = memory_kinds();

fam_start('Erinnerungen', 'erinnerungen', $user);
fam_heading('Geschichten und Anekdoten', '<em>Erinnerungen</em>', '<a class="btn btn-lime btn-sm" href="erinnerung_edit.php">Geschichte erzählen</a>');
?>
<form class="toolbar no-print" method="get">
  <input type="search" name="q" value="<?= h($search) ?>" placeholder="Suchen …">
  <select name="kind" onchange="this.form.submit()"><option value="">Alle Arten</option><?php foreach ($kinds as $k => $l): ?><option value="<?= $k ?>"<?= $kindF === $k ? ' selected' : '' ?>><?= h($l) ?></option><?php endforeach ?></select>
  <select name="person" onchange="this.form.submit()"><option value="">Alle Personen</option><?php foreach ($persons as $p): ?><option value="<?= (int)$p['id'] ?>"<?= $personF === (int)$p['id'] ? ' selected' : '' ?>><?= h(person_label($p)) ?></option><?php endforeach ?></select>
  <label class="check"><input type="checkbox" name="mine" value="1" onchange="this.form.submit()"<?= $mine ? ' checked' : '' ?>> Von mir</label>
  <button class="btn btn-outline btn-sm">Suchen</button>
</form>
<?php if (!$list): ?>
  <div class="empty">Noch keine Geschichten. Was wurde bei euch früher am Küchentisch erzählt? Hier ist der Platz dafür.</div>
<?php else: ?>
<div class="tl">
<?php $curY = null; foreach ($list as $m):
  $y = $m['year'] ? (string)$m['year'] : 'Ohne Jahr';
  if ($y !== $curY) { echo '<div class="tl-year">' . h($y) . '</div>'; $curY = $y; } ?>
  <a class="mem<?= $m['image'] ? '' : ' no-img' ?>" href="erinnerung.php?id=<?= (int)$m['id'] ?>">
    <?php if ($m['image']): ?><div class="im"><img src="<?= h(img_url('memories', $m['image'], true)) ?>" alt="" loading="lazy"></div><?php endif ?>
    <div class="b"><div class="t"><?= h($m['title']) ?></div>
      <div class="x"><?= h(truncate_text($m['body'], 220)) ?></div>
      <div class="m"><span class="tag"><?= h($kinds[$m['kind']] ?? 'Geschichte') ?></span>
        <?php if ($m['date_text']): ?><span><?= h($m['date_text']) ?></span><?php endif ?>
        <?php foreach (isset($tags[(int)$m['id']]) ? $tags[(int)$m['id']] : array() as $t): ?><span>👤 <?= h(person_label($t)) ?></span><?php endforeach ?>
        <span>von <?= h(user_name($m['user_id'])) ?></span>
        <?php if ($m['hearts']): ?><span>♥ <?= (int)$m['hearts'] ?></span><?php endif ?><?php if ($m['ncom']): ?><span>💬 <?= (int)$m['ncom'] ?></span><?php endif ?></div></div>
  </a>
<?php endforeach ?>
</div>
<?php endif;
fam_end();
