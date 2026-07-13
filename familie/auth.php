<?php
declare(strict_types=1);

define('DB_PATH', __DIR__ . '/data/nellessen.db');
define('SESSION_LIFETIME', 60 * 60 * 8); // 8 hours

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("PRAGMA journal_mode=WAL");
    db_init($pdo);
    return $pdo;
}

function db_init(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            username   TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            name       TEXT    NOT NULL,
            email      TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            password   TEXT    NOT NULL,
            role       TEXT    NOT NULL DEFAULT 'member',
            active     INTEGER NOT NULL DEFAULT 1,
            created_at TEXT    NOT NULL DEFAULT (datetime('now')),
            last_login TEXT
        );
        CREATE TABLE IF NOT EXISTS sessions (
            token      TEXT    PRIMARY KEY,
            user_id    INTEGER NOT NULL,
            expires_at TEXT    NOT NULL,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // Create default admin if no users exist
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $pdo->prepare("
            INSERT INTO users (username, name, email, password, role)
            VALUES (?, ?, ?, ?, 'admin')
        ")->execute([
            'thomas',
            'Thomas Nellessen',
            'info@nellessen.de',
            password_hash('Nellessen2025!', PASSWORD_BCRYPT)
        ]);
    }
}

function session_start_secure(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/familie',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function auth_user(): ?array {
    session_start_secure();
    if (empty($_SESSION['user_id'])) return null;

    // Validate session age
    if (empty($_SESSION['expires_at']) || time() > $_SESSION['expires_at']) {
        session_destroy();
        return null;
    }

    $user = db()->prepare("SELECT * FROM users WHERE id=? AND active=1")->execute([$_SESSION['user_id']])
        ? db()->prepare("SELECT * FROM users WHERE id=? AND active=1") : null;

    $stmt = db()->prepare("SELECT * FROM users WHERE id=? AND active=1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(): array {
    $user = auth_user();
    if (!$user) {
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if ($user['role'] !== 'admin') {
        header('Location: index.php?err=noaccess');
        exit;
    }
    return $user;
}

function login(string $username, string $password): ?array {
    $stmt = db()->prepare("SELECT * FROM users WHERE username=? AND active=1");
    $stmt->execute([trim($username)]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) return null;

    session_start_secure();
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['expires_at'] = time() + SESSION_LIFETIME;

    db()->prepare("UPDATE users SET last_login=datetime('now') WHERE id=?")->execute([$user['id']]);
    return $user;
}

function logout(): void {
    session_start_secure();
    session_destroy();
    setcookie(session_name(), '', 0, '/familie');
    header('Location: login.php');
    exit;
}

function csrf_token(): string {
    session_start_secure();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    session_start_secure();
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(403);
        die('CSRF validation failed.');
    }
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
