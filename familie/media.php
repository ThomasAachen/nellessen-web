<?php
// Liefert hochgeladene Bilder nur an angemeldete Nutzer aus.
define('FAM_READONLY_SESSION', true);
require_once __DIR__ . '/auth.php';

$user = auth_user();
if (!$user) { http_response_code(403); exit('Nicht angemeldet.'); }

$kind = get_str('k');
$file = get_str('f');
if (!in_array($kind, array('photos', 'recipes', 'persons', 'memories'), true) || !preg_match('/^[a-f0-9]{32}\.(jpg|png|gif|webp)$/', $file)) {
    http_response_code(404); exit('Nicht gefunden.');
}
$path = UPLOAD_DIR . '/' . $kind . '/' . $file;
if (get_str('s') === 'thumb') {
    $t = UPLOAD_DIR . '/' . $kind . '/thumb/' . $file;
    if (is_file($t)) $path = $t;
}
if (!is_file($path)) { http_response_code(404); exit('Nicht gefunden.'); }

$types = array('jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp');
$ext = substr($file, -3) === 'peg' ? 'jpg' : pathinfo($file, PATHINFO_EXTENSION);
$mtime = filemtime($path);
$etag = '"' . md5($path . $mtime . filesize($path)) . '"';

header('Content-Type: ' . $types[$ext]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=604800');
header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag)) {
    http_response_code(304); exit;
}
if (get_str('dl') === '1') {
    $name = 'nellessen-' . substr($file, 0, 8) . '.' . $ext;
    $orig = $kind === 'photos' ? val('SELECT orig_name FROM photos WHERE file = ?', array($file)) : '';
    if ($orig) $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $orig);
    header('Content-Disposition: attachment; filename="' . $name . '"');
}
header('Content-Length: ' . filesize($path));
readfile($path);
