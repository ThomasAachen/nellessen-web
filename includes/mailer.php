<?php
/**
 * Mailversand über SMTP (PHPMailer). Zugangsdaten stehen in der Datenbank
 * (Adminbereich, Einstellungen) und nicht im Code oder im Repository.
 */
require_once __DIR__ . '/../familie/auth.php';

function mailer_config() {
    return array(
        'host'      => setting_get('smtp_host', 'smtp.ionos.de'),
        'port'      => (int)setting_get('smtp_port', '587'),
        'user'      => setting_get('smtp_user', 'info@nellessen.de'),
        'pass'      => setting_get('smtp_pass', ''),
        'from'      => setting_get('smtp_from', 'info@nellessen.de'),
        'from_name' => setting_get('smtp_from_name', 'Familie Nellessen'),
        'contact_to'=> setting_get('contact_to', 'info@nellessen.de'),
    );
}

function mailer_ready() {
    $c = mailer_config();
    return $c['pass'] !== '';
}

/**
 * $opts: reply_to (array(mail, name)), bcc (array von Adressen)
 * Rückgabe: array(bool ok, string fehlertext)
 */
function send_mail($to, $subject, $body, $opts = array()) {
    $c = mailer_config();
    if ($c['pass'] === '') {
        return array(false, 'Das SMTP-Passwort ist noch nicht hinterlegt (Adminbereich, Einstellungen).');
    }
    require_once __DIR__ . '/../phpmailer/Exception.php';
    require_once __DIR__ . '/../phpmailer/PHPMailer.php';
    require_once __DIR__ . '/../phpmailer/SMTP.php';
    try {
        $m = new \PHPMailer\PHPMailer\PHPMailer(true);
        $m->isSMTP();
        $m->Host = $c['host'];
        $m->SMTPAuth = true;
        $m->Username = $c['user'];
        $m->Password = $c['pass'];
        $m->SMTPSecure = $c['port'] == 465 ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $m->Port = $c['port'];
        $m->CharSet = 'UTF-8';
        $m->Timeout = 15;
        $m->setFrom($c['from'], $c['from_name']);
        $m->addAddress($to);
        if (!empty($opts['reply_to'])) $m->addReplyTo($opts['reply_to'][0], isset($opts['reply_to'][1]) ? $opts['reply_to'][1] : '');
        if (!empty($opts['bcc'])) foreach ($opts['bcc'] as $b) $m->addBCC($b);
        $m->isHTML(false);
        $m->Subject = $subject;
        $m->Body = $body;
        $m->send();
        return array(true, '');
    } catch (\Exception $e) {
        error_log('nellessen mail: ' . $e->getMessage());
        return array(false, 'Der Versand ist fehlgeschlagen.');
    }
}
