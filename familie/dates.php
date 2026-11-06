<?php
/**
 * Familientermine: Geburtstage, Hochzeitstage (aus dem Stammbaum) und eigene Termine.
 * Dazu die Erinnerungsmail, die beim ersten Seitenaufruf des Tages (oder per cron.php) verschickt wird.
 */
require_once __DIR__ . '/auth.php';

if (function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/Berlin');

function person_label($p) {
    $n = trim((isset($p['first_name']) ? $p['first_name'] : '') . ' ' . (isset($p['last_name']) ? $p['last_name'] : ''));
    return $n !== '' ? $n : 'Unbekannt';
}

function is_leap($y) { return ($y % 4 === 0 && $y % 100 !== 0) || $y % 400 === 0; }

/** Monat/Tag in einem Jahr; der 29. Februar rückt in Nicht-Schaltjahren auf den 28. */
function md_in_year($month, $day, $year) {
    if ($month === 2 && $day === 29 && !is_leap($year)) $day = 28;
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

/**
 * Alle Termine zwischen $from und $to (YYYY-MM-DD, inklusive), sortiert.
 * Eintrag: date, kind (birthday|anniversary|memorial|event), title, sub, years, link, event_id
 */
function family_dates($from, $to) {
    $y1 = (int)substr($from, 0, 4);
    $y2 = (int)substr($to, 0, 4);
    $out = array();
    $push = function ($date, $kind, $title, $sub, $years, $link, $eventId) use (&$out, $from, $to) {
        if ($date >= $from && $date <= $to) {
            $out[] = array('date' => $date, 'kind' => $kind, 'title' => $title, 'sub' => $sub,
                           'years' => $years, 'link' => $link, 'event_id' => $eventId);
        }
    };

    $persons = rows('SELECT * FROM persons');
    $byId = array();
    foreach ($persons as $p) { $byId[(int)$p['id']] = $p; }

    foreach ($persons as $p) {
        $name = person_label($p);
        $link = 'stammbaum.php?p=' . (int)$p['id'];
        if (is_full_date($p['birth_date'])) {
            $by = (int)substr($p['birth_date'], 0, 4);
            $bm = (int)substr($p['birth_date'], 5, 2);
            $bd = (int)substr($p['birth_date'], 8, 2);
            $dead = !empty($p['death_date']);
            for ($y = max($y1, $by + 1); $y <= $y2; $y++) {
                $age = $y - $by;
                if ($dead) {
                    $push(md_in_year($bm, $bd, $y), 'memorial', $name . ' wäre ' . $age . ' geworden', 'Geburtstag, in Erinnerung', $age, $link, 0);
                } else {
                    $push(md_in_year($bm, $bd, $y), 'birthday', $name . ' hat Geburtstag', 'wird ' . $age, $age, $link, 0);
                }
            }
        }
        if (is_full_date($p['death_date'])) {
            $dy0 = (int)substr($p['death_date'], 0, 4);
            $dm = (int)substr($p['death_date'], 5, 2);
            $dd = (int)substr($p['death_date'], 8, 2);
            for ($y = max($y1, $dy0 + 1); $y <= $y2; $y++) {
                $push(md_in_year($dm, $dd, $y), 'memorial', 'Todestag von ' . $name, ($y - $dy0) . '. Jahrestag', $y - $dy0, $link, 0);
            }
        }
    }

    foreach (rows('SELECT * FROM partnerships') as $s) {
        if (!is_full_date($s['start_date']) || !empty($s['end_date'])) continue;
        if (!isset($byId[(int)$s['person_a']]) || !isset($byId[(int)$s['person_b']])) continue;
        $a = $byId[(int)$s['person_a']]; $b = $byId[(int)$s['person_b']];
        if (!empty($a['death_date']) || !empty($b['death_date'])) continue;
        $sy = (int)substr($s['start_date'], 0, 4);
        $sm = (int)substr($s['start_date'], 5, 2);
        $sd = (int)substr($s['start_date'], 8, 2);
        $label = $s['kind'] === 'marriage' ? 'Hochzeitstag' : 'Jahrestag';
        for ($y = max($y1, $sy + 1); $y <= $y2; $y++) {
            $n = $y - $sy;
            $push(md_in_year($sm, $sd, $y), 'anniversary', $label . ' ' . person_label($a) . ' und ' . person_label($b),
                  $n . '. ' . $label . anniversary_name($n), $n, 'stammbaum.php?p=' . (int)$a['id'], 0);
        }
    }

    foreach (rows('SELECT * FROM events') as $e) {
        if (!is_full_date($e['event_date'])) continue;
        $ey = (int)substr($e['event_date'], 0, 4);
        $em = (int)substr($e['event_date'], 5, 2);
        $ed = (int)substr($e['event_date'], 8, 2);
        $sub = trim((string)$e['location']);
        if ((int)$e['yearly'] === 1) {
            for ($y = max($y1, $ey); $y <= $y2; $y++) {
                $years = $y - $ey;
                $s2 = $sub;
                if ($years > 0) $s2 = trim(($years) . '. Jahr' . ($sub !== '' ? ' · ' . $sub : ''));
                $push(md_in_year($em, $ed, $y), 'event', $e['title'], $s2, $years, 'termine.php#e' . (int)$e['id'], (int)$e['id']);
            }
        } else {
            $push($e['event_date'], 'event', $e['title'], $sub, 0, 'termine.php#e' . (int)$e['id'], (int)$e['id']);
        }
    }

    usort($out, function ($a, $b) {
        $c = strcmp($a['date'], $b['date']);
        return $c !== 0 ? $c : strcmp($a['title'], $b['title']);
    });
    return $out;
}

function anniversary_name($n) {
    $m = array(1 => ' (Papierhochzeit)', 5 => ' (Hölzerne Hochzeit)', 10 => ' (Rosenhochzeit)', 15 => ' (Gläserne Hochzeit)',
               20 => ' (Porzellanhochzeit)', 25 => ' (Silberhochzeit)', 30 => ' (Perlenhochzeit)', 35 => ' (Leinenhochzeit)',
               40 => ' (Rubinhochzeit)', 45 => ' (Saphirhochzeit)', 50 => ' (Goldene Hochzeit)', 55 => ' (Smaragdhochzeit)',
               60 => ' (Diamantene Hochzeit)', 65 => ' (Eiserne Hochzeit)', 70 => ' (Gnadenhochzeit)');
    return isset($m[$n]) ? $m[$n] : '';
}

function today_ymd() { return date('Y-m-d'); }

function days_until($ymd) {
    $a = new DateTime(today_ymd());
    $b = new DateTime($ymd);
    return (int)$a->diff($b)->format('%r%a');
}

function when_text($ymd) {
    $d = days_until($ymd);
    if ($d === 0) return 'heute';
    if ($d === 1) return 'morgen';
    if ($d === -1) return 'gestern';
    if ($d > 0) return 'in ' . $d . ' Tagen';
    return 'vor ' . abs($d) . ' Tagen';
}

function weekday_de($ymd) {
    $w = de_weekdays();
    return $w[(int)date('w', strtotime($ymd))];
}

/* ───────────── Erinnerungsmail ───────────── */

function reminders_maybe_run() {
    if (setting_get('reminders_enabled', '0') !== '1') return;
    if (setting_get('reminder_last', '') === today_ymd()) return;
    if ((int)date('G') < 7) return;
    if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
    @ignore_user_abort(true);
    run_reminders(false);
}

/** Verschickt höchstens einmal pro Tag. Gibt einen Statustext zurück. */
function run_reminders($force) {
    if (setting_get('reminders_enabled', '0') !== '1' && !$force) return 'Erinnerungen sind ausgeschaltet.';
    $today = today_ymd();
    // Atomar sperren, damit zwei gleichzeitige Aufrufe nicht doppelt senden
    q("INSERT OR IGNORE INTO settings (skey, sval) VALUES ('reminder_last', '')");
    if (!$force) {
        $st = q("UPDATE settings SET sval = ? WHERE skey = 'reminder_last' AND sval <> ?", array($today, $today));
        if ($st->rowCount() < 1) return 'Heute wurde schon gesendet.';
    } else {
        setting_set('reminder_last', $today);
    }

    require_once __DIR__ . '/../includes/mailer.php';
    $offsets = array();
    foreach (explode(',', setting_get('reminder_days', '7,1')) as $o) {
        $o = trim($o);
        if ($o !== '' && ctype_digit($o) && (int)$o <= 60) $offsets[(int)$o] = true;
    }
    $offsets = array_keys($offsets);
    sort($offsets);
    $lines = array();
    foreach ($offsets as $o) {
        $day = date('Y-m-d', strtotime($today . ' +' . $o . ' days'));
        foreach (family_dates($day, $day) as $d) {
            if ($d['kind'] === 'memorial') continue;
            $when = $o === 0 ? 'Heute' : ($o === 1 ? 'Morgen' : 'In ' . $o . ' Tagen');
            $lines[] = $when . ' (' . weekday_de($day) . ', ' . de_date($day) . '): ' . $d['title'] . ($d['sub'] !== '' ? ' - ' . $d['sub'] : '');
        }
    }
    if (!$lines) { setting_set('reminder_status', $today . ': nichts zu melden'); return 'Keine anstehenden Termine.'; }

    $bcc = array();
    foreach (rows('SELECT email FROM users WHERE active = 1 AND notify = 1') as $u) {
        if (filter_var($u['email'], FILTER_VALIDATE_EMAIL)) $bcc[] = $u['email'];
    }
    if (!$bcc) { setting_set('reminder_status', $today . ': keine Empfänger'); return 'Keine Empfänger.'; }

    $c = mailer_config();
    $body = "Hallo,\n\ndas steht in der Familie Nellessen an:\n\n" . implode("\n", $lines)
          . "\n\nAlle Termine: https://www.nellessen.de/familie/termine.php\n"
          . "Du kannst diese Erinnerung in deinem Profil abstellen: https://www.nellessen.de/familie/profil.php\n";
    list($ok, $err) = send_mail($c['from'], 'Familienkalender: ' . count($lines) . ($lines && count($lines) === 1 ? ' Termin' : ' Termine'), $body, array('bcc' => $bcc));
    setting_set('reminder_status', $today . ($ok ? ': gesendet an ' . count($bcc) . ' Personen' : ': Fehler: ' . $err));
    return $ok ? 'Gesendet an ' . count($bcc) . ' Personen.' : 'Fehler: ' . $err;
}
