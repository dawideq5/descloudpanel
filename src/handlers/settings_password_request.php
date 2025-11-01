<?php
// Plik: /src/handlers/settings_password_request.php
// Publiczny, nie wymaga logowania

// === POPRAWKA ŚCIEŻKI ===
// Ten plik jest publiczny, musi ładować własne zależności.
require_once __DIR__ . '/../../config/boot.php';

// === ZMIANA: Uruchomienie sesji dla add_log() i komunikatów ===
session_start();

global $pdo;

$email = $_POST['email'] ?? null;
// $password_current = $_POST['password_current'] ?? null; // Usunięte zgodnie z prośbą

if (empty($email)) {
    $_SESSION['error_message'] = 'Musisz podać adres e-mail.';
    redirect('/reset_password');
}

try {
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Taki sam komunikat jak przy sukcesie, aby nie ujawniać, czy e-mail istnieje
        $_SESSION['success_message'] = 'Jeśli konto o podanym adresie e-mail istnieje, wysłaliśmy na nie link do resetowania hasła.';
        redirect('/reset_password');
    }

    // === ZMIANA: USUNIĘTO BLOK WERYFIKACJI OBECNEGO HASŁA ===
    // Logika weryfikacji hasła została usunięta zgodnie z prośbą.

    // Wygeneruj token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // Ważny 1 godzinę

    $stmt_token = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
    $stmt_token->execute([$token, $expires, $user['id']]);

    // Wyślij e-mail
    $reset_link = APP_URL . '/reset_password?token=' . $token;
    
    $subject = 'Resetowanie hasła';
    $body = "Otrzymaliśmy prośbę o zresetowanie hasła dla Twojego konta.<br><br>" .
            "Kliknij poniższy link, aby ustawić nowe hasło:<br>" .
            "<a href='{$reset_link}'>{$reset_link}</a><br><br>" .
            "Jeśli to nie Ty prosiłeś o zmianę, zignoruj tę wiadomość.<br>" .
            "Link wygaśnie za 1 godzinę.";

    if (!send_email($user['email'], $subject, $body)) {
        throw new Exception('Nie udało się wysłać e-maila do resetowania hasła. Spróbuj ponownie.');
    }
    
    // Potrzebujemy funkcji redirect(), która jest w auth.php.
    // auth.php ładuje functions.php, więc add_log też zadziała.
    require_once __DIR__ . '/../auth.php';
    add_log('PASSWORD_RESET_REQUEST', "Wysłano link do resetowania hasła dla {$email}.", 'INFO', $user['id']);
    
    $_SESSION['success_message'] = 'Jeśli konto o podanym adresie e-mail istnieje, wysłaliśmy na nie link do resetowania hasła.';
    redirect('/reset_password');

} catch (Exception $e) {
    error_log("Błąd wysyłania resetu hasła: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd serwera podczas próby wysłania wiadomości e-mail.';
    
    // Musimy załadować auth.php, aby użyć redirect() w razie błędu
    if (!function_exists('redirect')) {
        require_once __DIR__ . '/../auth.php';
    }
    redirect('/reset_password');
}