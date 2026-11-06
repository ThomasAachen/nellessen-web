<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/dates.php';
require_once __DIR__ . '/../includes/common.php';

function fam_tabs() {
    return array(
        'index'        => array('index.php', 'Übersicht'),
        'fotos'        => array('fotos.php', 'Fotos'),
        'termine'      => array('termine.php', 'Termine'),
        'rezepte'      => array('rezepte.php', 'Rezepte'),
        'erinnerungen' => array('erinnerungen.php', 'Erinnerungen'),
        'stammbaum'    => array('stammbaum.php', 'Stammbaum'),
    );
}

function fam_start($title, $active, $user, $opts = array()) {
    $css = @filemtime(__DIR__ . '/fam.css');
    $extra = '<meta name="csrf" content="' . h(csrf_token()) . '">'
           . '<link rel="stylesheet" href="fam.css?v=' . (int)$css . '">';
    page_head($title . ' · Familie Nellessen', 'Geschützter Familienbereich', $extra);
    site_nav('familie', true);
    echo '<main class="fam-wrap"><div class="container' . (!empty($opts['narrow']) ? ' container--sm' : '') . '">';
    if (empty($opts['bare'])) {
        echo '<div class="fam-bar no-print"><nav class="fam-nav">';
        foreach (fam_tabs() as $key => $t) {
            echo '<a href="' . $t[0] . '"' . ($key === $active ? ' class="active"' : '') . '>' . h($t[1]) . '</a>';
        }
        echo '</nav><div class="fam-user"><span class="avatar">' . h(initials($user['name'])) . '</span>'
           . '<a href="profil.php" class="fam-user-name">' . h($user['name']) . '</a>';
        if ($user['role'] === 'admin') echo '<a href="admin.php" class="btn btn-outline btn-sm">Admin</a>';
        echo '<a href="logout.php" class="btn btn-outline btn-sm">Abmelden</a></div></div>';
    }
    foreach (flash_pull() as $f) {
        echo '<div class="alert ' . ($f[0] === 'err' ? 'alert-err' : 'alert-ok') . ' no-print">' . h($f[1]) . '</div>';
    }
}

function fam_end($scripts = '') {
    echo '</div></main>';
    $js = @filemtime(__DIR__ . '/fam.js');
    site_footer('<script src="fam.js?v=' . (int)$js . '"></script>' . $scripts);
    reminders_maybe_run();
}

/** Kleiner Abschnittskopf */
function fam_heading($eyebrow, $title_html, $actions_html = '') {
    echo '<div class="page-head"><div><p class="eyebrow">' . h($eyebrow) . '</p><h1 class="page-title">' . $title_html . '</h1></div>';
    if ($actions_html !== '') echo '<div class="page-actions no-print">' . $actions_html . '</div>';
    echo '</div>';
}

function avatar_html($name, $size = 32) {
    return '<span class="avatar" style="width:' . (int)$size . 'px;height:' . (int)$size . 'px;font-size:' . round($size * 0.34, 1) . 'px">' . h(initials($name)) . '</span>';
}

function truncate_text($s, $n) {
    $s = trim(preg_replace('/\s+/', ' ', (string)$s));
    return mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n)) . ' …' : $s;
}

/** Text mit Zeilenumbrüchen sicher ausgeben und Links anklickbar machen */
function rich_text($s) {
    $s = h($s);
    $s = preg_replace('~(https?://[^\s<]+)~', '<a href="$1" target="_blank" rel="noopener nofollow">$1</a>', $s);
    $paras = preg_split('/\n{2,}/', trim(str_replace("\r", '', $s)));
    $out = '';
    foreach ($paras as $p) { if ($p !== '') $out .= '<p>' . nl2br($p) . '</p>'; }
    return $out;
}

function reaction_button($type, $id, $count, $mine) {
    return '<button type="button" class="react' . ($mine ? ' on' : '') . '" data-react="' . h($type) . ':' . (int)$id . '" title="Gefällt mir">'
         . '<span class="heart">' . ($mine ? '♥' : '♡') . '</span> <span class="n">' . (int)$count . '</span></button>';
}

function reaction_info($type, $id, $userId) {
    $n = (int)val('SELECT COUNT(*) FROM reactions WHERE target_type = ? AND target_id = ?', array($type, $id));
    $mine = (int)val('SELECT COUNT(*) FROM reactions WHERE target_type = ? AND target_id = ? AND user_id = ?', array($type, $id, $userId)) > 0;
    return array($n, $mine);
}

/** Kommentarbereich (serverseitig gerendert, Aktionen laufen über fam.js) */
function comments_block($type, $id, $user) {
    $list = rows('SELECT * FROM comments WHERE target_type = ? AND target_id = ? ORDER BY id ASC', array($type, $id));
    echo '<div class="comments"><h3 class="sub-h">Kommentare (' . count($list) . ')</h3>';
    foreach ($list as $c) {
        echo '<div class="comment">' . avatar_html(user_name($c['user_id']), 30)
           . '<div class="c-body"><div class="c-head"><strong>' . h(user_name($c['user_id'])) . '</strong> <span class="muted small">' . h(rel_time($c['created_at'])) . '</span>';
        if (is_owner_or_admin($user, $c['user_id'])) {
            echo ' <button type="button" class="link-btn" data-api-click="comment_delete" data-id="' . (int)$c['id'] . '" data-confirm="Kommentar löschen?">löschen</button>';
        }
        echo '</div><div class="c-text">' . nl2br(h($c['body'])) . '</div></div></div>';
    }
    echo '<form data-api="comment_add" class="comment-form no-print">'
       . '<input type="hidden" name="type" value="' . h($type) . '"><input type="hidden" name="id" value="' . (int)$id . '">'
       . '<textarea name="body" rows="2" placeholder="Kommentar schreiben …" required maxlength="2000"></textarea>'
       . '<button class="btn btn-lime btn-sm" type="submit">Senden</button></form></div>';
}
