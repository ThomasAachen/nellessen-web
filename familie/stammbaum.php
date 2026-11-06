<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$canEdit = has_role($user, 'editor');

function person_age($p) {
    if (!preg_match('/^(\d{4})/', (string)$p['birth_date'], $m)) return null;
    $by = (int)$m[1];
    if (is_full_date($p['birth_date'])) {
        $end = !empty($p['death_date']) ? (is_full_date($p['death_date']) ? $p['death_date'] : null) : date('Y-m-d');
        if ($end === null) return !empty($p['death_date']) ? max(0, (int)substr($p['death_date'], 0, 4) - $by) : null;
        $age = (int)substr($end, 0, 4) - $by;
        if (substr($end, 5) < substr($p['birth_date'], 5)) $age--;
        return max(0, $age);
    }
    $endY = !empty($p['death_date']) ? (int)substr($p['death_date'], 0, 4) : (int)date('Y');
    return max(0, $endY - $by);
}

$persons = array();
foreach (rows('SELECT * FROM persons ORDER BY id') as $p) {
    $age = person_age($p);
    $persons[] = array(
        'id' => (int)$p['id'], 'first' => (string)$p['first_name'], 'last' => (string)$p['last_name'], 'birth_name' => (string)$p['birth_name'],
        'gender' => (string)$p['gender'], 'bd' => (string)$p['birth_date'], 'bd_txt' => fmt_partial($p['birth_date']), 'bp' => (string)$p['birth_place'],
        'dd' => (string)$p['death_date'], 'dd_txt' => fmt_partial($p['death_date']), 'dp' => (string)$p['death_place'],
        'bio' => (string)$p['bio'], 'photo' => $p['photo'] ? img_url('persons', $p['photo'], true) : '', 'age' => $age,
        'user' => $p['user_id'] ? user_name($p['user_id']) : '', 'can_del' => is_owner_or_admin($user, $p['created_by']),
        'by' => $p['created_by'] ? user_name($p['created_by']) : '',
    );
}
$links = array();
foreach (rows('SELECT parent_id, child_id, kind FROM parent_links') as $l) $links[] = array((int)$l['parent_id'], (int)$l['child_id'], $l['kind']);
$partners = array();
foreach (rows('SELECT * FROM partnerships') as $s) {
    $partners[] = array((int)$s['person_a'], (int)$s['person_b'], $s['kind'], fmt_partial($s['start_date']), fmt_partial($s['end_date']), (int)$s['id']);
}
$memCount = array();
foreach (rows('SELECT person_id, COUNT(*) n FROM memory_people GROUP BY person_id') as $m) $memCount[(int)$m['person_id']] = (int)$m['n'];

fam_start('Stammbaum', 'stammbaum', $user);
fam_heading('Wer gehört dazu?', '<em>Stamm</em>baum', $canEdit ? '<a class="btn btn-lime btn-sm" href="person_edit.php">Person hinzufügen</a>' : '');
?>
<div class="toolbar no-print">
  <input type="search" id="treeSearch" placeholder="Person suchen …" autocomplete="off">
  <span class="muted small" id="treeInfo"></span>
  <span style="flex:1"></span>
  <?php if (!$canEdit): ?><span class="muted tiny">Der Stammbaum wird von Redakteuren und Admins gepflegt. Hinweise gern an Thomas.</span><?php endif ?>
</div>
<?php if (!$persons): ?>
  <div class="empty">Der Stammbaum ist noch leer.<?php if ($canEdit): ?> Fang mit <a href="person_edit.php">der ersten Person</a> an und füge dann Eltern, Partner und Kinder hinzu.<?php endif ?></div>
<?php else: ?>
<div class="tree-viewport" id="treeVp">
  <div class="tree-canvas" id="treeCanvas"><svg id="treeSvg"></svg></div>
  <div class="tree-tools"><button type="button" id="zIn" aria-label="Vergrößern">+</button><button type="button" id="zOut" aria-label="Verkleinern">−</button><button type="button" id="zFit" aria-label="Alles anzeigen" title="Alles anzeigen">⤢</button></div>
  <aside class="tree-panel" id="treePanel"></aside>
</div>
<div class="tree-legend"><span><i style="background:var(--sky)"></i>männlich</span><span><i style="background:#f472b6"></i>weiblich</span><span><i style="background:#64748b"></i>divers / unbekannt</span><span>Ziehen zum Verschieben, Mausrad oder Zwei-Finger-Geste zum Zoomen, Klick auf eine Person für Details.</span></div>
<?php endif;
fam_end('<script>window.TREE=' . json_attr(array('persons' => $persons, 'links' => $links, 'partners' => $partners, 'canEdit' => $canEdit, 'mem' => (object)$memCount, 'focus' => get_int('p'))) . ';</script><script src="stammbaum.js?v=' . (int)@filemtime(__DIR__ . '/stammbaum.js') . '"></script>');
