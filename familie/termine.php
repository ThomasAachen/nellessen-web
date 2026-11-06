<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$canEdit = has_role($user, 'editor');

if (is_post()) {
    csrf_check();
    if (!$canEdit) { flash('Termine pflegen dürfen Redakteure und Admins.', 'err'); redirect('termine.php'); }
    $do = post_str('do');
    if ($do === 'save') {
        $id = post_int('id');
        $title = post_str('title');
        $date = parse_partial_date(post_str('event_date'));
        $kind = 'event';
        if ($title === '' || mb_strlen($title) > 120) flash('Bitte gib einen Titel ein.', 'err');
        elseif (!$date || !is_full_date($date)) flash('Bitte gib ein vollständiges Datum ein, zum Beispiel 24.12.2026.', 'err');
        else {
            $args = array($title, $date, isset($_POST['yearly']) ? 1 : 0, mb_substr(post_str('location'), 0, 160), mb_substr(post_str('notes'), 0, 1000));
            if ($id && val('SELECT COUNT(*) FROM events WHERE id = ?', array($id))) {
                q('UPDATE events SET title = ?, event_date = ?, yearly = ?, location = ?, notes = ? WHERE id = ?', array_merge($args, array($id)));
            } else {
                q('INSERT INTO events (title, event_date, yearly, location, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)', array_merge($args, array($user['id'])));
            }
            flash('Der Termin wurde gespeichert.');
        }
    } elseif ($do === 'delete') {
        q('DELETE FROM events WHERE id = ?', array(post_int('id')));
        flash('Der Termin wurde gelöscht.');
    }
    redirect('termine.php');
}

$range = get_str('range', 'year');
$kindF = get_str('kind');
$today = today_ymd();
$endMap = array('month' => '+31 days', 'year' => '+365 days', 'two' => '+730 days');
$end = date('Y-m-d', strtotime($today . ' ' . (isset($endMap[$range]) ? $endMap[$range] : '+365 days')));
$list = family_dates($today, $end);
if ($kindF !== '') $list = array_values(array_filter($list, function ($d) use ($kindF) { return $d['kind'] === $kindF; }));
$next = array();
foreach (family_dates($today, date('Y-m-d', strtotime($today . ' +400 days'))) as $d) {
    if ($d['kind'] === 'birthday' && count($next) < 3) $next[] = $d;
}
$one = array_values(array_filter(family_dates($today, date('Y-m-d', strtotime($today . ' +400 days'))), function ($d) { return $d['kind'] === 'event'; }));
$events = rows('SELECT * FROM events ORDER BY event_date DESC');
$base = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
$kindLabel = array('birthday' => 'Geburtstag', 'anniversary' => 'Hochzeitstag', 'memorial' => 'Gedenken', 'event' => 'Termin');

fam_start('Termine', 'termine', $user);
fam_heading('Geburtstage und mehr', '<em>Familien</em>termine',
    ($canEdit ? '<button class="btn btn-lime btn-sm" data-open="evOverlay" data-ev-new>Termin anlegen</button>' : '')
  . '<button class="btn btn-outline btn-sm" data-open="icalOverlay">Kalender abonnieren</button>');
if ($next): ?>
<div class="next-cards" style="margin-bottom:2rem">
  <?php foreach ($next as $d): $dd = days_until($d['date']); ?>
  <a class="next-card" href="<?= h($d['link']) ?>"><div class="big"><?= $dd === 0 ? 'Heute' : $dd ?></div><div class="ss"><?= $dd === 0 ? '' : ($dd === 1 ? 'Tag bis' : 'Tage bis') ?></div>
    <div class="tt"><?= h($d['title']) ?></div><div class="ss"><?= h(weekday_de($d['date']) . ', ' . de_date($d['date'])) ?> · <?= h($d['sub']) ?></div></a>
  <?php endforeach ?>
</div>
<?php endif ?>

<div class="toolbar no-print">
  <?php foreach (array('' => 'Alle', 'birthday' => 'Geburtstage', 'anniversary' => 'Hochzeitstage', 'event' => 'Termine', 'memorial' => 'Gedenktage') as $k => $l): ?>
    <a class="chip<?= $kindF === $k ? ' on' : '' ?>" href="termine.php?range=<?= h($range) ?><?= $k !== '' ? '&kind=' . $k : '' ?>"><?= $l ?></a>
  <?php endforeach ?>
  <span style="flex:1"></span>
  <?php foreach (array('month' => '31 Tage', 'year' => '1 Jahr', 'two' => '2 Jahre') as $k => $l): ?>
    <a class="chip<?= $range === $k ? ' on' : '' ?>" href="termine.php?range=<?= $k ?><?= $kindF !== '' ? '&kind=' . h($kindF) : '' ?>"><?= $l ?></a>
  <?php endforeach ?>
</div>

<?php if (!$list): ?>
  <div class="empty">In diesem Zeitraum steht nichts an. Geburtstage erscheinen automatisch, sobald Personen im Stammbaum ein vollständiges Geburtsdatum haben.</div>
<?php else:
  $mo = de_months(); $curM = '';
  foreach ($list as $d):
    $m = $mo[(int)substr($d['date'], 5, 2)] . ' ' . substr($d['date'], 0, 4);
    if ($m !== $curM) { echo '<div class="cal-month">' . h($m) . '</div>'; $curM = $m; }
    $dd = days_until($d['date']); ?>
  <div class="cal-item k-<?= h($d['kind']) ?>"<?= $d['event_id'] ? ' id="e' . (int)$d['event_id'] . '"' : '' ?>>
    <div class="d"><b><?= (int)substr($d['date'], 8, 2) ?></b><span><?= h(mb_substr(weekday_de($d['date']), 0, 2)) ?></span></div>
    <div><div class="t"><?= $d['link'] && $d['kind'] !== 'event' ? '<a href="' . h($d['link']) . '">' . h($d['title']) . '</a>' : h($d['title']) ?></div>
      <div class="s"><?= h($kindLabel[$d['kind']]) ?><?= $d['sub'] !== '' ? ' · ' . h($d['sub']) : '' ?></div></div>
    <div class="when<?= $dd >= 0 && $dd <= 7 ? ' soon' : '' ?>"><?= h(when_text($d['date'])) ?></div>
  </div>
<?php endforeach; endif ?>

<?php if ($canEdit): ?>
<div class="panel" style="margin-top:2.5rem">
  <h2>Eigene Termine verwalten</h2>
  <?php if (!$events): ?><p class="muted small">Noch keine eigenen Termine. Geburtstage und Hochzeitstage kommen aus dem Stammbaum.</p><?php endif ?>
  <?php foreach ($events as $e): ?>
  <div class="row between" style="padding:.6rem 0;border-top:1px solid rgba(255,255,255,.06)">
    <div><strong><?= h($e['title']) ?></strong> <span class="muted small">· <?= h(de_date($e['event_date'])) ?><?= $e['yearly'] ? ' · jährlich' : '' ?><?= $e['location'] ? ' · ' . h($e['location']) : '' ?></span></div>
    <div class="row">
      <button class="link-btn" data-open="evOverlay" data-ev="<?= h(json_attr(array('id' => (int)$e['id'], 'title' => $e['title'], 'date' => de_date($e['event_date']), 'yearly' => (int)$e['yearly'], 'location' => (string)$e['location'], 'notes' => (string)$e['notes']))) ?>">bearbeiten</button>
      <form method="post" class="inline-form" data-confirm="Termin löschen?"><?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$e['id'] ?>"><button class="link-btn" style="color:#f87171">löschen</button></form>
    </div>
  </div>
  <?php endforeach ?>
</div>

<div class="overlay" id="evOverlay"><div class="modal">
  <button class="modal-close" data-close="evOverlay" aria-label="Schließen">×</button>
  <h2 id="evH">Termin anlegen</h2>
  <form method="post" id="evForm"><?= csrf_field() ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" value="0">
    <div class="form-group"><label>Titel</label><input name="title" required maxlength="120" placeholder="z. B. Familientreffen"></div>
    <div class="form-2">
      <div class="form-group"><label>Datum</label><input name="event_date" required placeholder="TT.MM.JJJJ"></div>
      <div class="form-group"><label>Ort (optional)</label><input name="location" maxlength="160"></div>
    </div>
    <div class="form-group"><label>Notizen (optional)</label><textarea name="notes" rows="3" maxlength="1000"></textarea></div>
    <div class="form-group"><label class="check" style="text-transform:none;letter-spacing:0;font-size:.9rem"><input type="checkbox" name="yearly" value="1"> Jedes Jahr wiederholen</label></div>
    <button class="btn btn-lime btn-sm">Speichern</button>
  </form>
</div></div>
<?php endif ?>

<div class="overlay" id="icalOverlay"><div class="modal">
  <button class="modal-close" data-close="icalOverlay" aria-label="Schließen">×</button>
  <h2>Kalender abonnieren</h2>
  <p>Mit diesem persönlichen Link zeigt dein Kalender (Apple, Google, Outlook) alle Familientermine automatisch an. Bitte nicht weitergeben.</p>
  <div class="copybox"><input id="icalUrl" readonly value="<?= h($base . 'ical.php?t=' . $user['ical_token']) ?>"><button type="button" class="btn btn-outline btn-sm" data-copy="#icalUrl">Kopieren</button></div>
  <p class="tiny" style="margin-top:1rem">Google Kalender: „Weitere Kalender“, „Per URL“. iPhone: Einstellungen, Kalender, Accounts, „Abonnierten Kalender hinzufügen“.</p>
</div></div>
<?php
fam_end('<script>
document.addEventListener("click",function(e){
  var f=document.getElementById("evForm");if(!f)return;
  var b=e.target.closest("[data-ev],[data-ev-new]");if(!b)return;
  var d=b.dataset.ev?JSON.parse(b.dataset.ev):{id:0,title:"",date:"",yearly:0,location:"",notes:""};
  f.elements.id.value=d.id;f.elements.title.value=d.title;f.elements.event_date.value=d.date;f.elements.location.value=d.location;f.elements.notes.value=d.notes;f.elements.yearly.checked=!!d.yearly;
  document.getElementById("evH").textContent=d.id?"Termin bearbeiten":"Termin anlegen";
});
</script>');
