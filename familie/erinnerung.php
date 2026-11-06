<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$m = row('SELECT * FROM memories WHERE id = ?', array(get_int('id')));
if (!$m) { flash('Diese Geschichte gibt es nicht.', 'err'); redirect('erinnerungen.php'); }
$kinds = array('story' => 'Geschichte', 'anecdote' => 'Anekdote', 'tradition' => 'Tradition', 'milestone' => 'Meilenstein');

if (is_post()) {
    csrf_check();
    if (post_str('do') === 'delete' && is_owner_or_admin($user, $m['user_id'])) {
        image_delete('memories', $m['image']);
        q("DELETE FROM comments WHERE target_type = 'memory' AND target_id = ?", array($m['id']));
        q("DELETE FROM reactions WHERE target_type = 'memory' AND target_id = ?", array($m['id']));
        q('DELETE FROM memories WHERE id = ?', array($m['id']));
        flash('Die Geschichte wurde gelöscht.');
        redirect('erinnerungen.php');
    }
    redirect('erinnerung.php?id=' . (int)$m['id']);
}
$people = rows('SELECT p.* FROM memory_people mp JOIN persons p ON p.id = mp.person_id WHERE mp.memory_id = ?', array($m['id']));
list($hn, $hm) = reaction_info('memory', $m['id'], $user['id']);
$can = is_owner_or_admin($user, $m['user_id']);

fam_start($m['title'], 'erinnerungen', $user, array('narrow' => true));
?>
<p class="no-print" style="margin-bottom:1rem"><a href="erinnerungen.php" class="muted small">← Alle Erinnerungen</a></p>
<p class="eyebrow"><?= h($kinds[$m['kind']] ?? 'Geschichte') ?><?= $m['year'] ? ' · ' . (int)$m['year'] : '' ?><?= $m['date_text'] ? ' · ' . h($m['date_text']) : '' ?></p>
<h1 class="page-title" style="font-size:clamp(1.8rem,4vw,2.8rem);margin:.4rem 0 1rem"><?= h($m['title']) ?></h1>
<p class="muted small" style="margin-bottom:1.5rem">erzählt von <strong><?= h(user_name($m['user_id'])) ?></strong>, <?= h(rel_time($m['created_at'])) ?>
  <?php foreach ($people as $p): ?> · <a href="stammbaum.php?p=<?= (int)$p['id'] ?>">👤 <?= h(person_label($p)) ?></a><?php endforeach ?></p>
<?php if ($m['image']): ?><img class="story lead" style="width:100%;max-height:460px;object-fit:cover;margin-bottom:1.5rem" src="<?= h(img_url('memories', $m['image'])) ?>" alt=""><?php endif ?>
<div class="story"><?= rich_text($m['body']) ?></div>
<div class="row no-print" style="margin-top:2rem">
  <?= reaction_button('memory', $m['id'], $hn, $hm) ?>
  <button class="btn btn-outline btn-sm" onclick="window.print()">Drucken</button>
  <?php if ($can): ?><a class="btn btn-outline btn-sm" href="erinnerung_edit.php?id=<?= (int)$m['id'] ?>">Bearbeiten</a>
  <form method="post" class="inline-form" data-confirm="Diese Geschichte endgültig löschen?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><button class="btn btn-danger btn-sm">Löschen</button></form><?php endif ?>
</div>
<?php comments_block('memory', $m['id'], $user);
fam_end();
