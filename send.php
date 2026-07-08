<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Methode nicht erlaubt.']));
}

// ── Sanitize & Validate ──────────────────────────────────
$name    = trim(strip_tags($_POST['name']    ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

if (mb_strlen($name) < 2 || mb_strlen($name) > 100)
    exit(json_encode(['success' => false, 'error' => 'Ungültiger Name.']));
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    exit(json_encode(['success' => false, 'error' => 'Ungültige E-Mail-Adresse.']));
if (mb_strlen($message) < 10 || mb_strlen($message) > 3000)
    exit(json_encode(['success' => false, 'error' => 'Nachricht zu kurz oder zu lang (max. 3000 Zeichen).']));
if (preg_match('/(http|https|www\.|viagra|casino)/i', $message))
    exit(json_encode(['success' => false, 'error' => 'Spam erkannt.']));

// ── PHPMailer via IONOS SMTP ─────────────────────────────
require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);

    // SMTP — IONOS
    $mail->isSMTP();
    $mail->Host       = 'smtp.ionos.de';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@nellessen.de';
    $mail->Password   = 'Williwub09!';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Absender & Empfänger
    $mail->setFrom('info@nellessen.de', 'nellessen.de');
    $mail->addAddress('info@nellessen.de', 'Familie Nellessen');
    $mail->addReplyTo($email, $name);

    // Inhalt
    $mail->isHTML(false);
    $mail->Subject = 'Kontaktanfrage nellessen.de von ' . $name;
    $mail->Body    =
        "Kontaktanfrage über nellessen.de\n" .
        str_repeat('-', 40) . "\n" .
        "Name:    $name\n" .
        "E-Mail:  $email\n" .
        "Datum:   " . date('d.m.Y H:i:s') . "\n\n" .
        "Nachricht:\n$message\n";

    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('nellessen mailer error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'E-Mail konnte nicht gesendet werden. Bitte schreib direkt an info@nellessen.de.'
    ]);
}
