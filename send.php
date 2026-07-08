<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt.']);
    exit;
}

// Sanitize
$name    = trim(strip_tags($_POST['name']    ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

// Validate
if (mb_strlen($name) < 2 || mb_strlen($name) > 100)
    exit(json_encode(['success'=>false,'error'=>'Ungültiger Name.']));
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    exit(json_encode(['success'=>false,'error'=>'Ungültige E-Mail.']));
if (mb_strlen($message) < 10 || mb_strlen($message) > 3000)
    exit(json_encode(['success'=>false,'error'=>'Nachricht zu kurz oder zu lang.']));
if (preg_match('/(http|https|www\.|viagra|casino)/i', $message))
    exit(json_encode(['success'=>false,'error'=>'Spam erkannt.']));

$to      = 'info@nellessen.de';
$subject = '=?UTF-8?B?' . base64_encode('Kontaktanfrage nellessen.de von ' . $name) . '?=';
$body    = "Kontaktanfrage über nellessen.de\n";
$body   .= str_repeat('-', 40) . "\n";
$body   .= "Name:    $name\n";
$body   .= "E-Mail:  $email\n";
$body   .= "Datum:   " . date('d.m.Y H:i:s') . "\n\n";
$body   .= "Nachricht:\n$message\n";

// IONOS-kompatible Header: From = echte Mailbox
$headers  = "From: info@nellessen.de\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

// Zusatzparameter: Envelope-Sender (wichtig für IONOS)
$params = '-f info@nellessen.de';

$sent = mail($to, $subject, $body, $headers, $params);

// Debug-Info ins Error-Log schreiben (nicht an Browser)
if (!$sent) {
    error_log('nellessen mail() failed: to=' . $to . ' name=' . $name);
}

echo json_encode(['success' => $sent, 'error' => $sent ? '' : 'E-Mail konnte nicht gesendet werden. Bitte schreib direkt an info@nellessen.de']);
