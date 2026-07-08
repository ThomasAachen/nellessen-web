<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt.']);
    exit;
}

// Rate limiting via session (basic protection)
session_start();
$now = time();
if (isset($_SESSION['last_send']) && ($now - $_SESSION['last_send']) < 60) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Bitte warte eine Minute zwischen zwei Nachrichten.']);
    exit;
}

// Sanitize inputs
$name    = trim(strip_tags($_POST['name']    ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

// Validate
$errors = [];
if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
    $errors[] = 'Bitte gib einen gültigen Namen ein.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Bitte gib eine gültige E-Mail-Adresse ein.';
}
if (mb_strlen($message, 'UTF-8') < 10 || mb_strlen($message, 'UTF-8') > 3000) {
    $errors[] = 'Nachricht muss zwischen 10 und 3000 Zeichen lang sein.';
}
// Basic spam check
if (preg_match('/(http|https|www\.|\.com|viagra|casino)/i', $message)) {
    $errors[] = 'Nachricht enthält unerlaubte Inhalte.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// Build email
$to      = 'info@nellessen.de';
$subject = '=?UTF-8?B?' . base64_encode('Kontaktanfrage nellessen.de von ' . $name) . '?=';
$body    = "Neue Kontaktanfrage über nellessen.de\n";
$body   .= str_repeat('─', 40) . "\n\n";
$body   .= "Name:      " . $name    . "\n";
$body   .= "E-Mail:    " . $email   . "\n";
$body   .= "Zeitpunkt: " . date('d.m.Y H:i:s T') . "\n\n";
$body   .= "Nachricht:\n" . $message . "\n\n";
$body   .= str_repeat('─', 40) . "\n";
$body   .= "Diese Nachricht wurde über das Kontaktformular auf nellessen.de gesendet.";

$headers  = "From: noreply@nellessen.de\r\n";
$headers .= "Reply-To: " . $name . " <" . $email . ">\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    $_SESSION['last_send'] = $now;
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'E-Mail konnte nicht gesendet werden. Bitte wende dich direkt an info@nellessen.de.']);
}
