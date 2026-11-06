<?php
// Optionaler Aufruf per Cronjob: cron.php?key=...
require_once __DIR__ . '/dates.php';
header('Content-Type: text/plain; charset=utf-8');
$key = setting_get('cron_key', '');
$given = isset($_GET['key']) ? (string)$_GET['key'] : '';
if ($key === '' || !hash_equals($key, $given)) { http_response_code(403); exit('Verboten.'); }
echo run_reminders(false) . "\n";
