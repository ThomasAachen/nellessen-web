<?php
// Persönlicher Kalender-Feed (Abo). Zugriff nur mit dem geheimen Token aus dem Profil.
require_once __DIR__ . '/dates.php';

$t = isset($_GET['t']) ? (string)$_GET['t'] : '';
$u = preg_match('/^[a-f0-9]{32}$/', $t) ? row('SELECT id FROM users WHERE ical_token = ? AND active = 1', array($t)) : null;
if (!$u) { http_response_code(403); exit('Ungültiger Link.'); }

function ics_esc($s) { return str_replace(array('\\', ';', ',', "\r", "\n"), array('\\\\', '\;', '\\,', '', '\\n'), (string)$s); }
function ics_fold($line) {
    $out = ''; 
    while (strlen($line) > 73) {
        $cut = 73;
        while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) $cut--;
        $out .= substr($line, 0, $cut) . "\r\n "; $line = substr($line, $cut);
    }
    return $out . $line;
}

$from = date('Y-m-d', strtotime('-1 month'));
$to = date('Y-m-d', strtotime('+2 years'));
$L = array('BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Familie Nellessen//DE', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
           'X-WR-CALNAME:Familie Nellessen', 'X-WR-TIMEZONE:Europe/Berlin', 'REFRESH-INTERVAL;VALUE=DURATION:P1D');
$stamp = gmdate('Ymd\THis\Z');
foreach (family_dates($from, $to) as $d) {
    $day = str_replace('-', '', $d['date']);
    $next = str_replace('-', '', date('Y-m-d', strtotime($d['date'] . ' +1 day')));
    $uid = md5($d['kind'] . $d['date'] . $d['title']) . '@nellessen.de';
    $L[] = 'BEGIN:VEVENT';
    $L[] = 'UID:' . $uid;
    $L[] = 'DTSTAMP:' . $stamp;
    $L[] = 'DTSTART;VALUE=DATE:' . $day;
    $L[] = 'DTEND;VALUE=DATE:' . $next;
    $L[] = 'SUMMARY:' . ics_esc($d['title']);
    if ($d['sub'] !== '') $L[] = 'DESCRIPTION:' . ics_esc($d['sub']);
    $L[] = 'TRANSP:TRANSPARENT';
    $L[] = 'END:VEVENT';
}
$L[] = 'END:VCALENDAR';
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="familie-nellessen.ics"');
header('Cache-Control: private, max-age=3600');
echo implode("\r\n", array_map('ics_fold', $L)) . "\r\n";
