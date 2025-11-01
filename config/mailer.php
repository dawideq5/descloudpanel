<?php
// Plik: /config/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);

    try {
        // Konfiguracja serwera
        $mail->isSMTP();
        
        // TRYB DIAGNOSTYCZNY (zmienimy na 0 po naprawie)
        $mail->SMTPDebug = 2; 
        $mail->Debugoutput = 'error_log'; // Dodano, aby logować błędy SMTP do logów serwera
        
        $mail->CharSet = 'UTF-8';
        
        // === POPRAWKA KRTYTYCZNA: Używamy LOCALHOST dla wewnętrznej wysyłki Home.pl ===
        $mail->Host       = 'localhost';
        $mail->SMTPAuth   = true; // Wymagane, nawet dla localhosta na Home.pl
        
        $mail->Username   = 'noreply@descloud.pl'; // Z Pana pliku
        $mail->Password   = 'Mt5Db4Yu'; // Z Pana pliku
        // Zabezpieczenie na wypadek, gdyby APP_NAME nie było zdefiniowane
        $appName = defined('APP_NAME') ? APP_NAME : 'Descloud'; 

        $mail->setFrom('noreply@descloud.pl', $appName); 
        
        $mail->addReplyTo($mail->Username, $appName);

        // Użyj portu 25 (standardowy port dla lokalnej wysyłki)
        $mail->SMTPSecure = false; // Wyłączamy SSL/TLS przy połączeniu lokalnym
        $mail->Port       = 25; 

        // Ustawienia SSL są teraz niepotrzebne, ale je zostawiamy,
        // aby nie generować błędów, jeśli serwer je mimo to sprawdzi.
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        // === KONIEC POPRAWKI ===

        // USUNIĘTO: $mail->isHTML(true); - to jest teraz ustawiane poprawnie w handlerze
        // w zależności od potrzeb. Domyślnie PHPMailer użyje Plain Text, jeśli isHTML(false).
        
        return $mail;
        
    } catch (Exception $e) {
        // Zapisz błąd Mailera do logu serwera
        error_log("Błąd konfiguracji Mailera (przy inicjalizacji): {$e->getMessage()}");
        // Log błędu SMTP jest już zapisywany przez $mail->Debugoutput = 'error_log';
        
        // Wyrzucenie wyjątku z informacją, która jest logowana
        throw new Exception("Nie można skonfigurować serwera pocztowego. Sprawdź plik config/mailer.php i logi serwera.");
    }
}