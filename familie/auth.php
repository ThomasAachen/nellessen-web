<?php
/**
 * Kernbibliothek des Familienbereichs:
 * Datenbank (SQLite), Migration, Login, Rollen, CSRF, Hilfsfunktionen, Bildverarbeitung.
 * Läuft ab PHP 7.2.
 */
if (!function_exists('str_contains')) {
    function str_contains($h, $n) { return $n === '' || strpos($h, $n) !== false; }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return strlen(utf8_decode($s)); }
}
if (!function_exists('mb_substr')) {
    function mb_substr($s, $a, $l = null) { return $l === null ? substr($s, $a) : substr($s, $a, $l); }
}
date_default_timezone_set('Europe/Berlin');

define('FAM_DIR', __DIR__);
define('DB_PATH', FAM_DIR . '/data/nellessen.db');
define('UPLOAD_DIR', FAM_DIR . '/uploads');
define('SESSION_LIFETIME', 8 * 3600);
define('SCHEMA_VERSION', 3);
define('LEGACY_DEFAULT_PW', 'Nellessen2025!');

/* ───────────── Dateisystem ───────────── */

function ensure_dir($dir, $deny = false) {
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    if ($deny && is_dir($dir) && !is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess',
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n");
    }
}

/* ───────────── Datenbank ───────────── */

function db() {
    static $pdo = null;
    if ($pdo) return $pdo;
    ensure_dir(FAM_DIR . '/data', true);
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
    } catch (Exception $e) {
        http_response_code(500);
        die('Datenbank nicht verfügbar (PDO SQLite fehlt auf dem Server?).');
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = ON');
    db_migrate($pdo);
    return $pdo;
}

function q($sql, $params = array()) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function row($sql, $params = array())  { $r = q($sql, $params)->fetch(); return $r ? $r : null; }
function rows($sql, $params = array()) { return q($sql, $params)->fetchAll(); }
function val($sql, $params = array())  { return q($sql, $params)->fetchColumn(); }
function last_id() { return (int)db()->lastInsertId(); }

function db_migrate($pdo) {
    $ver = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    if ($ver >= SCHEMA_VERSION) return;

    $pdo->query('PRAGMA journal_mode = DELETE')->fetchAll();

    $ddl = array(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE COLLATE NOCASE,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE COLLATE NOCASE,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'member',
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            last_login TEXT,
            must_change INTEGER NOT NULL DEFAULT 0,
            notify INTEGER NOT NULL DEFAULT 1,
            ical_token TEXT
        )",
        "CREATE TABLE IF NOT EXISTS settings (skey TEXT PRIMARY KEY, sval TEXT)",
        "CREATE TABLE IF NOT EXISTS login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT, username TEXT, ts INTEGER)",
        "CREATE TABLE IF NOT EXISTS albums (
            id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT,
            created_by INTEGER, created_at TEXT NOT NULL DEFAULT (datetime('now')))",
        "CREATE TABLE IF NOT EXISTS photos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            album_id INTEGER REFERENCES albums(id) ON DELETE SET NULL,
            file TEXT NOT NULL, orig_name TEXT, caption TEXT, taken_at TEXT,
            width INTEGER, height INTEGER, bytes INTEGER,
            created_at TEXT NOT NULL DEFAULT (datetime('now')))",
        "CREATE INDEX IF NOT EXISTS idx_photos_album ON photos(album_id)",
        "CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            target_type TEXT NOT NULL, target_id INTEGER NOT NULL, user_id INTEGER NOT NULL,
            body TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT (datetime('now')))",
        "CREATE INDEX IF NOT EXISTS idx_comments_t ON comments(target_type, target_id)",
        "CREATE TABLE IF NOT EXISTS reactions (
            target_type TEXT NOT NULL, target_id INTEGER NOT NULL, user_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            PRIMARY KEY (target_type, target_id, user_id))",
        "CREATE TABLE IF NOT EXISTS persons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT, last_name TEXT, birth_name TEXT, gender TEXT,
            birth_date TEXT, birth_place TEXT, death_date TEXT, death_place TEXT,
            bio TEXT, photo TEXT, user_id INTEGER, created_by INTEGER,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT)",
        "CREATE TABLE IF NOT EXISTS parent_links (
            parent_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
            child_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
            kind TEXT NOT NULL DEFAULT 'bio',
            PRIMARY KEY (parent_id, child_id))",
        "CREATE TABLE IF NOT EXISTS partnerships (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            person_a INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
            person_b INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
            kind TEXT NOT NULL DEFAULT 'marriage', start_date TEXT, end_date TEXT, note TEXT)",
        "CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL, event_date TEXT NOT NULL, yearly INTEGER NOT NULL DEFAULT 0,
            kind TEXT NOT NULL DEFAULT 'event', location TEXT, notes TEXT,
            created_by INTEGER, created_at TEXT NOT NULL DEFAULT (datetime('now')))",
        "CREATE TABLE IF NOT EXISTS recipes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL, title TEXT NOT NULL, description TEXT, category TEXT,
            servings INTEGER NOT NULL DEFAULT 4, prep_min INTEGER, cook_min INTEGER,
            steps TEXT, note TEXT, source TEXT, image TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT)",
        "CREATE TABLE IF NOT EXISTS recipe_ingredients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
            pos INTEGER NOT NULL DEFAULT 0, amount REAL, unit TEXT, name TEXT NOT NULL)",
        "CREATE TABLE IF NOT EXISTS recipe_ratings (
            recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL, stars INTEGER NOT NULL,
            PRIMARY KEY (recipe_id, user_id))",
        "CREATE TABLE IF NOT EXISTS recipe_favs (
            recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL, PRIMARY KEY (recipe_id, user_id))",
        "CREATE TABLE IF NOT EXISTS memories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL,
            year INTEGER, date_text TEXT, kind TEXT NOT NULL DEFAULT 'story', image TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')), updated_at TEXT)",
        "CREATE TABLE IF NOT EXISTS memory_people (
            memory_id INTEGER NOT NULL REFERENCES memories(id) ON DELETE CASCADE,
            person_id INTEGER NOT NULL REFERENCES persons(id) ON DELETE CASCADE,
            PRIMARY KEY (memory_id, person_id))",
        "CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL, body TEXT NOT NULL, pinned INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')))",
    );
    foreach ($ddl as $sql) { $pdo->exec($sql); }

    // Spalten nachrüsten, falls die users-Tabelle aus einer älteren Version stammt
    $cols = array();
    foreach ($pdo->query('PRAGMA table_info(users)')->fetchAll() as $c) { $cols[$c['name']] = true; }
    if (empty($cols['must_change'])) $pdo->exec('ALTER TABLE users ADD COLUMN must_change INTEGER NOT NULL DEFAULT 0');
    if (empty($cols['notify']))      $pdo->exec('ALTER TABLE users ADD COLUMN notify INTEGER NOT NULL DEFAULT 1');
    if (empty($cols['ical_token']))  $pdo->exec('ALTER TABLE users ADD COLUMN ical_token TEXT');

    // Konten, die noch das alte, öffentlich bekannte Startpasswort haben, müssen es beim nächsten Login ändern
    foreach ($pdo->query('SELECT id, password, ical_token FROM users')->fetchAll() as $u) {
        if (password_verify(LEGACY_DEFAULT_PW, $u['password'])) {
            $pdo->prepare('UPDATE users SET must_change = 1 WHERE id = ?')->execute(array($u['id']));
        }
        if (empty($u['ical_token'])) {
            $pdo->prepare('UPDATE users SET ical_token = ? WHERE id = ?')->execute(array(bin2hex(random_bytes(16)), $u['id']));
        }
    }
    $pdo->exec('PRAGMA user_version = ' . (int)SCHEMA_VERSION);
}

function setting_get($key, $default = '') {
    $v = val('SELECT sval FROM settings WHERE skey = ?', array($key));
    return ($v === false || $v === null) ? $default : $v;
}
function setting_set($key, $value) {
    q('INSERT OR REPLACE INTO settings (skey, sval) VALUES (?, ?)', array($key, (string)$value));
}

/* ───────────── Session, Login, Rechte ───────────── */

function session_start_secure() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('NELLSESS');
        session_set_cookie_params(array(
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/familie',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ));
        session_start();
    }
}

function auth_user() {
    static $cache = false;
    if ($cache !== false) return $cache;
    session_start_secure();
    $user = null;
    if (!empty($_SESSION['user_id']) && !empty($_SESSION['expires_at']) && time() <= $_SESSION['expires_at']) {
        $user = row('SELECT * FROM users WHERE id = ? AND active = 1', array($_SESSION['user_id']));
    }
    if (!$user && !empty($_SESSION['user_id'])) { $_SESSION = array(); }
    if (defined('FAM_READONLY_SESSION')) session_write_close();
    $cache = $user;
    return $cache;
}

function role_rank($r) {
    $m = array('member' => 1, 'editor' => 2, 'admin' => 3);
    return isset($m[$r]) ? $m[$r] : 0;
}
function role_label($r) {
    $m = array('member' => 'Mitglied', 'editor' => 'Redakteur', 'admin' => 'Admin');
    return isset($m[$r]) ? $m[$r] : $r;
}
function has_role($user, $min) { return $user && role_rank($user['role']) >= role_rank($min); }
function is_owner_or_admin($user, $ownerId) {
    return $user && ($user['role'] === 'admin' || (int)$user['id'] === (int)$ownerId);
}

function require_login() {
    $user = auth_user();
    if (!$user) {
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    $script = basename($_SERVER['SCRIPT_NAME']);
    if ((int)$user['must_change'] === 1 && !in_array($script, array('profil.php', 'logout.php'), true)) {
        redirect('profil.php?force=1');
    }
    return $user;
}
function require_role($min) {
    $user = require_login();
    if (!has_role($user, $min)) {
        flash('Dafür fehlt dir die Berechtigung.', 'err');
        redirect('index.php');
    }
    return $user;
}

function login_throttled($username) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    q('DELETE FROM login_attempts WHERE ts < ?', array(time() - 86400));
    $n = (int)val('SELECT COUNT(*) FROM login_attempts WHERE ts > ? AND (ip = ? OR username = ?)',
        array(time() - 900, $ip, strtolower($username)));
    return $n >= 10;
}
function login_failed($username) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    q('INSERT INTO login_attempts (ip, username, ts) VALUES (?, ?, ?)', array($ip, strtolower($username), time()));
}

function login($username, $password) {
    $username = trim($username);
    $u = row('SELECT * FROM users WHERE (username = ? OR email = ?) AND active = 1', array($username, $username));
    if (!$u || !password_verify($password, $u['password'])) return null;
    session_start_secure();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['expires_at'] = time() + SESSION_LIFETIME;
    q("UPDATE users SET last_login = datetime('now') WHERE id = ?", array($u['id']));
    return $u;
}

function logout() {
    session_start_secure();
    $_SESSION = array();
    session_destroy();
    setcookie('NELLSESS', '', time() - 3600, '/familie');
    redirect('login.php');
}

/* ───────────── CSRF, Flash, Request ───────────── */

function csrf_token() {
    session_start_secure();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check() {
    session_start_secure();
    $sent = isset($_POST['csrf']) ? $_POST['csrf'] : (isset($_SERVER['HTTP_X_CSRF']) ? $_SERVER['HTTP_X_CSRF'] : '');
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$sent)) {
        if (strpos(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '', 'json') !== false || basename($_SERVER['SCRIPT_NAME']) === 'api.php') {
            json_out(array('ok' => false, 'error' => 'Sitzung abgelaufen. Bitte Seite neu laden.'), 403);
        }
        http_response_code(403);
        die('Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden.');
    }
}
function flash($msg, $type = 'ok') {
    session_start_secure();
    $_SESSION['flash'][] = array($type, $msg);
}
function flash_pull() {
    session_start_secure();
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
    unset($_SESSION['flash']);
    return $f;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect($url) { header('Location: ' . $url); exit; }
function is_post() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function post_str($k, $d = '') { return isset($_POST[$k]) && !is_array($_POST[$k]) ? trim((string)$_POST[$k]) : $d; }
function post_int($k, $d = 0) { return isset($_POST[$k]) && is_numeric($_POST[$k]) ? (int)$_POST[$k] : $d; }
function get_int($k, $d = 0) { return isset($_GET[$k]) && is_numeric($_GET[$k]) ? (int)$_GET[$k] : $d; }
function get_str($k, $d = '') { return isset($_GET[$k]) && !is_array($_GET[$k]) ? trim((string)$_GET[$k]) : $d; }
function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function json_attr($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/* ───────────── Nutzer ───────────── */

function users_map() {
    static $m = null;
    if ($m === null) {
        $m = array();
        foreach (rows('SELECT id, name, username, role, active FROM users') as $u) $m[(int)$u['id']] = $u;
    }
    return $m;
}
function user_name($id) {
    $m = users_map();
    return isset($m[(int)$id]) ? $m[(int)$id]['name'] : 'Unbekannt';
}
function initials($name) {
    $parts = preg_split('/\s+/', trim((string)$name));
    $s = '';
    foreach ($parts as $p) { if ($p !== '') $s .= mb_substr($p, 0, 1); if (mb_strlen($s) >= 2) break; }
    return strtoupper($s);
}
function new_token() { return bin2hex(random_bytes(16)); }

function gen_password($len = 10) {
    $chars = 'abcdefghjkmnpqrstuvwxyzACDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $len; $i++) $s .= $chars[random_int(0, strlen($chars) - 1)];
    return $s;
}

/* ───────────── Datum und Zeit ───────────── */

function de_months() {
    return array('', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
}
function de_weekdays() { return array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'); }
function is_full_date($iso) { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$iso); }

/** 'YYYY-MM-DD' → '12.03.1985' (oder lang: '12. März 1985') */
function de_date($iso, $long = false) {
    if (!is_full_date($iso)) return fmt_partial($iso);
    list($y, $m, $d) = explode('-', $iso);
    if ($long) { $mo = de_months(); return (int)$d . '. ' . $mo[(int)$m] . ' ' . $y; }
    return $d . '.' . $m . '.' . $y;
}
/** Teildaten: '1925', '1925-03', '1925-03-12' */
function fmt_partial($iso) {
    $iso = (string)$iso;
    if ($iso === '') return '';
    if (preg_match('/^\d{4}$/', $iso)) return $iso;
    if (preg_match('/^(\d{4})-(\d{2})$/', $iso, $m)) { $mo = de_months(); return $mo[(int)$m[2]] . ' ' . $m[1]; }
    if (is_full_date($iso)) return de_date($iso);
    return $iso;
}
/** Eingabe 'TT.MM.JJJJ' | 'MM.JJJJ' | 'JJJJ' | 'JJJJ-MM-TT' → ISO-Teildatum oder null; false bei Fehler */
function parse_partial_date($s) {
    $s = trim((string)$s);
    if ($s === '') return null;
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $s, $m)) {
        return checkdate((int)$m[2], (int)$m[1], (int)$m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : false;
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
        return checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $s : false;
    }
    if (preg_match('/^(\d{1,2})\.(\d{4})$/', $s, $m)) {
        return ((int)$m[1] >= 1 && (int)$m[1] <= 12) ? sprintf('%04d-%02d', $m[2], $m[1]) : false;
    }
    if (preg_match('/^\d{4}$/', $s)) return $s;
    return false;
}
/** ISO-Teildatum → Eingabeformat */
function input_partial($iso) {
    $iso = (string)$iso;
    if (is_full_date($iso)) return de_date($iso);
    if (preg_match('/^(\d{4})-(\d{2})$/', $iso, $m)) return $m[2] . '.' . $m[1];
    return $iso;
}
function fmt_dt($utc) {
    if (!$utc) return '';
    try {
        $d = new DateTime($utc, new DateTimeZone('UTC'));
        $d->setTimezone(new DateTimeZone('Europe/Berlin'));
        return $d->format('d.m.Y H:i');
    } catch (Exception $e) { return $utc; }
}
function rel_time($utc) {
    if (!$utc) return '';
    $t = strtotime($utc . ' UTC');
    $diff = time() - $t;
    if ($diff < 60) return 'gerade eben';
    if ($diff < 3600) { $n = (int)floor($diff / 60); return 'vor ' . $n . ($n == 1 ? ' Minute' : ' Minuten'); }
    if ($diff < 86400) { $n = (int)floor($diff / 3600); return 'vor ' . $n . ($n == 1 ? ' Stunde' : ' Stunden'); }
    if ($diff < 86400 * 14) { $n = (int)floor($diff / 86400); return 'vor ' . $n . ($n == 1 ? ' Tag' : ' Tagen'); }
    return fmt_dt($utc);
}

/* ───────────── Bilder ───────────── */

function upload_error_text($code) {
    $m = array(
        UPLOAD_ERR_INI_SIZE => 'Die Datei ist größer als das Limit des Servers.',
        UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß.',
        UPLOAD_ERR_PARTIAL => 'Der Upload wurde unterbrochen. Bitte noch einmal versuchen.',
        UPLOAD_ERR_NO_FILE => 'Es wurde keine Datei gewählt.',
        UPLOAD_ERR_NO_TMP_DIR => 'Auf dem Server fehlt ein temporärer Ordner.',
        UPLOAD_ERR_CANT_WRITE => 'Die Datei konnte nicht gespeichert werden.',
    );
    return isset($m[$code]) ? $m[$code] : 'Unbekannter Upload-Fehler.';
}

/**
 * Speichert ein hochgeladenes Bild neu kodiert (entfernt Metadaten und mögliche Schadinhalte).
 * Rückgabe: array(file, w, h, bytes, taken)
 */
function image_save_upload($f, $kind, $max = 2400, $thumb = 480) {
    @ini_set('memory_limit', '384M');
    if (!isset($f['error']) || is_array($f['error'])) throw new Exception('Ungültiger Upload.');
    if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception(upload_error_text($f['error']));
    if ($f['size'] > 30 * 1024 * 1024) throw new Exception('Die Datei ist zu groß (maximal 30 MB).');
    if (!is_uploaded_file($f['tmp_name']) && !(defined('FAM_TESTING') && is_file($f['tmp_name']))) throw new Exception('Ungültiger Upload.');

    $info = @getimagesize($f['tmp_name']);
    if (!$info) throw new Exception('Das ist kein unterstütztes Bild. Bitte JPG, PNG, GIF oder WebP verwenden.');
    $type = $info[2];
    $dir = UPLOAD_DIR . '/' . $kind;
    ensure_dir(UPLOAD_DIR, true);
    ensure_dir($dir);
    ensure_dir($dir . '/thumb');
    $name = bin2hex(random_bytes(16));

    $creators = array(
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG => 'imagecreatefrompng',
        IMAGETYPE_GIF => 'imagecreatefromgif',
    );
    if (defined('IMAGETYPE_WEBP')) $creators[IMAGETYPE_WEBP] = 'imagecreatefromwebp';
    if (!isset($creators[$type])) throw new Exception('Dieses Bildformat wird nicht unterstützt. Bitte JPG, PNG, GIF oder WebP verwenden.');

    $taken = null;
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $ex = @exif_read_data($f['tmp_name']);
        if (is_array($ex)) {
            foreach (array('DateTimeOriginal', 'DateTimeDigitized', 'DateTime') as $k) {
                if (!empty($ex[$k]) && preg_match('/^(\d{4}):(\d{2}):(\d{2})/', $ex[$k], $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                    $taken = $m[1] . '-' . $m[2] . '-' . $m[3];
                    break;
                }
            }
        }
    }

    if (!function_exists('imagecreatetruecolor') || !function_exists($creators[$type])) {
        // Ohne GD: Original übernehmen, wenn es ein JPEG oder PNG ist
        if ($type !== IMAGETYPE_JPEG && $type !== IMAGETYPE_PNG) throw new Exception('Auf dem Server fehlt die Bildbibliothek (GD).');
        $ext = $type === IMAGETYPE_PNG ? 'png' : 'jpg';
        $dest = $dir . '/' . $name . '.' . $ext;
        if (!@move_uploaded_file($f['tmp_name'], $dest) && !@rename($f['tmp_name'], $dest)) throw new Exception('Die Datei konnte nicht gespeichert werden.');
        return array('file' => $name . '.' . $ext, 'w' => $info[0], 'h' => $info[1], 'bytes' => filesize($dest), 'taken' => $taken);
    }

    $src = @$creators[$type]($f['tmp_name']);
    if (!$src) throw new Exception('Das Bild konnte nicht gelesen werden.');

    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data') && function_exists('imagerotate')) {
        $ex = @exif_read_data($f['tmp_name']);
        $o = is_array($ex) && isset($ex['Orientation']) ? (int)$ex['Orientation'] : 1;
        $angle = array(3 => 180, 6 => -90, 8 => 90);
        if (isset($angle[$o])) { $rot = imagerotate($src, $angle[$o], 0); if ($rot) { imagedestroy($src); $src = $rot; } }
    }

    $w = imagesx($src); $h = imagesy($src);
    $save = function ($img, $path, $edge, $quality) use ($w, $h) {
        $scale = min(1, $edge / max($w, $h));
        $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imageinterlace($dst, true);
        imagejpeg($dst, $path, $quality);
        imagedestroy($dst);
        return array($nw, $nh);
    };
    $dest = $dir . '/' . $name . '.jpg';
    $dim = $save($src, $dest, $max, 86);
    $save($src, $dir . '/thumb/' . $name . '.jpg', $thumb, 80);
    imagedestroy($src);
    return array('file' => $name . '.jpg', 'w' => $dim[0], 'h' => $dim[1], 'bytes' => filesize($dest), 'taken' => $taken);
}

function image_delete($kind, $file) {
    if (!$file || !preg_match('/^[a-f0-9]{32}\.(jpg|png|gif|webp)$/', $file)) return;
    if (!in_array($kind, array('photos', 'recipes', 'persons', 'memories'), true)) return;
    @unlink(UPLOAD_DIR . '/' . $kind . '/' . $file);
    @unlink(UPLOAD_DIR . '/' . $kind . '/thumb/' . $file);
}
function img_url($kind, $file, $thumb = false) {
    if (!$file) return '';
    return 'media.php?k=' . $kind . '&f=' . $file . ($thumb ? '&s=thumb' : '');
}
