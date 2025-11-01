<?php
// Plik: /config/mailer.php
// Wersja 3 - Używa zmiennych środowiskowych

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);

    try {
        // Konfiguracja serwera
        $mail->isSMTP();
        
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = 'error_log';
        
        $mail->CharSet = 'UTF-8';
        
        $mail->Host       = $_ENV['MAILER_HOST'];
        $mail->SMTPAuth   = true;
        
        $mail->Username   = $_ENV['MAILER_USER'];
        $mail->Password   = $_ENV['MAILER_PASS'];
        
        $appName = defined('APP_NAME') ? APP_NAME : 'Descloud';
        $mail->setFrom($_ENV['MAILER_USER'], $appName);
        $mail->addReplyTo($_ENV['MAILER_USER'], $appName);

        $mail->SMTPSecure = false;
        $mail->Port       = 25; 

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        return $mail;
        
    } catch (Exception $e) {
        error_log("Błąd konfiguracji Mailera (przy inicjalizacji): {$e->getMessage()}");
        throw new Exception("Nie można skonfigurować serwera pocztowego. Sprawdź plik .env i logi serwera.");
    }
}
