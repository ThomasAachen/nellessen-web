<?php
/**
 * nellessen.de — Joomla Cleanup Script
 * Löscht alle Joomla!-1.5-Altdaten (2009)
 * Behält: glowinthedark/, index.html, send.php
 *
 * EINMALIG ausführen, dann löscht sich das Script selbst.
 * Aufruf: https://www.nellessen.de/cleanup.php?token=NellessenCleanup2025
 */

define('CLEANUP_TOKEN', 'NellessenCleanup2025');
define('BASE', __DIR__ . '/');

// ── Auth ────────────────────────────────────────────────
if (($_GET['token'] ?? '') !== CLEANUP_TOKEN) {
    http_response_code(403);
    die('403 Forbidden');
}

header('Content-Type: text/html; charset=UTF-8');

// ── Was wird gelöscht ───────────────────────────────────
$DELETE_DIRS = [
    'administrator',
    'cache',
    'components',
    'images',
    'includes',
    'language',
    'libraries',
    'logs',
    'media',
    'modules',
    'old_page_backup',
    'plugins',
    'templates',
    'tmp',
    'xmlrpc',
];

// Sonderfall: Ordner mit Sonderzeichen im Namen
$special = glob(BASE . '@!!!*', GLOB_ONLYDIR);

$DELETE_FILES = [
    'CHANGELOG.php',
    'configuration.php',
    'configuration.php-dist',
    'COPYRIGHT.php',
    'CREDITS.php',
    'htaccess.txt',
    'index.php',
    'index2.php',
    'INSTALL.php',
    'LICENSES.php',
    'LICENSE.php',
    'robots.txt',      // wird neu erstellt
];

// ── Hilfsfunktion: Verzeichnis rekursiv löschen ─────────
function rrmdir(string $dir): bool {
    if (!is_dir($dir)) return true;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
    }
    return rmdir($dir);
}

// ── Ausgabe ─────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>Cleanup — nellessen.de</title>
<style>
  body { font-family: monospace; background: #04060a; color: #c2f56a; padding: 2rem; }
  h1   { color: #fff; font-size: 1.4rem; margin-bottom: 1.5rem; }
  .ok  { color: #c2f56a; }
  .err { color: #f87171; }
  .skip{ color: #38bdf8; }
  .info{ color: #94a3b8; }
  hr   { border-color: #1a2e1a; margin: 1rem 0; }
</style>
</head>
<body>
<h1>🧹 nellessen.de — Joomla Cleanup</h1>
<pre>
<?php

echo "BASE: " . BASE . "\n\n";

// Ordner löschen
echo "── Verzeichnisse ─────────────────────────\n";
foreach ($DELETE_DIRS as $dir) {
    $path = BASE . $dir;
    if (!is_dir($path)) {
        echo "<span class='info'>SKIP  $dir/ (nicht vorhanden)</span>\n";
        continue;
    }
    if (rrmdir($path)) {
        echo "<span class='ok'>✓ DEL  $dir/</span>\n";
    } else {
        echo "<span class='err'>✗ FAIL $dir/</span>\n";
    }
}

// Sonderzeichenordner
foreach ($special as $path) {
    $name = basename($path);
    if (rrmdir($path)) {
        echo "<span class='ok'>✓ DEL  $name/</span>\n";
    } else {
        echo "<span class='err'>✗ FAIL $name/</span>\n";
    }
}

echo "\n── Dateien ───────────────────────────────\n";
foreach ($DELETE_FILES as $file) {
    $path = BASE . $file;
    if (!file_exists($path)) {
        echo "<span class='info'>SKIP  $file (nicht vorhanden)</span>\n";
        continue;
    }
    if (unlink($path)) {
        echo "<span class='ok'>✓ DEL  $file</span>\n";
    } else {
        echo "<span class='err'>✗ FAIL $file</span>\n";
    }
}

// Neuen robots.txt schreiben
echo "\n── Neu erstellen ─────────────────────────\n";
$robots = "User-agent: *\nAllow: /\nDisallow: /glowinthedark/\n\nSitemap: https://www.nellessen.de/sitemap.xml\n";
if (file_put_contents(BASE . 'robots.txt', $robots)) {
    echo "<span class='ok'>✓ NEU  robots.txt</span>\n";
} else {
    echo "<span class='err'>✗ FAIL robots.txt</span>\n";
}

// Was bleibt
echo "\n── Behalten ──────────────────────────────\n";
$keep = ['glowinthedark', 'index.html', 'send.php'];
foreach ($keep as $item) {
    $exists = file_exists(BASE . $item) || is_dir(BASE . $item);
    echo "<span class='skip'>✓ KEEP $item" . ($exists ? '' : ' (fehlt noch — nach Deployment OK)') . "</span>\n";
}

// Verzeichnis-Übersicht nach Cleanup
echo "\n── Verbleibende Einträge in " . BASE . " ──\n";
foreach (scandir(BASE) as $f) {
    if ($f === '.' || $f === '..' || $f === 'cleanup.php') continue;
    echo "<span class='info'>  $f</span>\n";
}

// Script löscht sich selbst
echo "\n── Self-destruct ─────────────────────────\n";
if (unlink(__FILE__)) {
    echo "<span class='ok'>✓ cleanup.php gelöscht — erledigt.</span>\n";
} else {
    echo "<span class='err'>✗ cleanup.php konnte sich nicht selbst löschen — bitte manuell entfernen!</span>\n";
}

echo "\n✅ Cleanup abgeschlossen.\n";
?>
</pre>
</body>
</html>
