<?php
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(array('success' => false, 'error' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Methode nicht erlaubt.', 405);

require __DIR__ . '/includes/mailer.php';

$name    = trim(strip_tags(isset($_POST['name']) ? (string)$_POST['name'] : ''));
$email   = trim(strip_tags(isset($_POST['email']) ? (string)$_POST['email'] : ''));
$message = trim(strip_tags(isset($_POST['message']) ? (string)$_POST['message'] : ''));

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) fail('Bitte gib einen gültigen Namen ein.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 200) fail('Bitte gib eine gültige E-Mail-Adresse ein.');
if (mb_strlen($message) < 10 || mb_strlen($message) > 3000) fail('Die Nachricht muss zwischen 10 und 3000 Zeichen lang sein.');
if (preg_match('/(https?:|www\.|viagra|casino)/i', $message)) fail('Die Nachricht enthält unerlaubte Inhalte.');

// Einfache Begrenzung: höchstens 5 Nachrichten pro Stunde und IP
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$n = (int)val("SELECT COUNT(*) FROM login_attempts WHERE username = 'contact' AND ip = ? AND ts > ?", array($ip, time() - 3600));
if ($n >= 5) fail('Es wurden zu viele Nachrichten gesendet. Bitte versuche es später noch einmal.', 429);

$c = mailer_config();
$body = "Kontaktanfrage über nellessen.de\n" . str_repeat('-', 40) . "\n"
      . "Name:   $name\nE-Mail: $email\nDatum:  " . date('d.m.Y H:i') . "\n\nNachricht:\n$message\n";

list($ok, $err) = send_mail($c['contact_to'], 'Kontaktanfrage nellessen.de von ' . $name, $body, array('reply_to' => array($email, $name)));
if ($ok) {
    q("INSERT INTO login_attempts (ip, username, ts) VALUES (?, 'contact', ?)", array($ip, time()));
    echo json_encode(array('success' => true));
} else {
    fail('Die Nachricht konnte nicht gesendet werden. Bitte schreib direkt an info@nellessen.de.', 500);
}
