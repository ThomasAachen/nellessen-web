<?php
require_once __DIR__ . '/layout.php';

function recipe_categories() {
    return array('Vorspeise', 'Suppe', 'Hauptgericht', 'Beilage', 'Salat', 'Dessert', 'Kuchen und Gebäck', 'Brot', 'Getränk', 'Sonstiges');
}

/** '1,5' | '1/2' | '1 1/2' | '½' → float oder null */
function parse_amount($s) {
    $s = trim((string)$s);
    if ($s === '') return null;
    $map = array('½' => '.5', '¼' => '.25', '¾' => '.75', '⅓' => '.3333', '⅔' => '.6667');
    $s = strtr($s, $map);
    $s = str_replace(',', '.', $s);
    if (preg_match('~^(\d+)\s+(\d+)/(\d+)$~', $s, $m) && (int)$m[3] > 0) return (int)$m[1] + $m[2] / $m[3];
    if (preg_match('~^(\d+)/(\d+)$~', $s, $m) && (int)$m[2] > 0) return $m[1] / $m[2];
    if (preg_match('~^(\d+)\s*\.(\d+)$~', $s) || preg_match('~^\d+(\.\d+)?$~', $s)) return (float)str_replace(' ', '', $s);
    if (preg_match('~^\.\d+$~', $s)) return (float)$s;
    return false;
}

function amount_text($a) {
    if ($a === null || $a === '') return '';
    $a = (float)$a;
    $r = rtrim(rtrim(number_format($a, 3, ',', ''), '0'), ',');
    return $r;
}

function recipe_rating_info($id) {
    $r = row('SELECT AVG(stars) a, COUNT(*) n FROM recipe_ratings WHERE recipe_id = ?', array($id));
    return array($r['n'] ? round((float)$r['a'], 1) : 0, (int)$r['n']);
}

function stars_static($avg) {
    $o = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) $o .= '<span class="' . ($avg >= $i - 0.25 ? 'on' : '') . '">★</span>';
    return $o . '</span>';
}
