<?php
// Plik: /src/handlers/settings_password_request.php
// Wersja 2 - Używa szablonu e-mail i 15-minutowego czasu wygaśnięcia

// Publiczny, nie wymaga logowania
require_once __DIR__ . '/../../config/boot.php';
require_once __DIR__ . '/../functions.php'; // Potrzebujemy send_templated_email

session_start();
global $pdo;

$email = $_POST['email'] ?? null;

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = 'Musisz podać prawidłowy adres e-mail.';
    redirect('/reset_password');
}

try {
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generuj token i ustaw czas wygaśnięcia na 15 minut
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 900); // 15 minut

        $stmt_token = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
        $stmt_token->execute([$token, $expires, $user['id']]);

        // Wyślij e-mail przy użyciu szablonu
        $reset_link = APP_URL . '/reset_password?token=' . $token;
        $subject = 'Resetowanie hasła';

        $message = "Otrzymaliśmy prośbę o zresetowanie hasła dla Twojego konta.<br><br>" .
                   "Kliknij poniższy przycisk, aby ustawić nowe hasło. Link jest ważny przez 15 minut.";

        $button_html = '<a href="' . $reset_link . '" class="button">Zresetuj hasło</a>';

        send_templated_email($user['email'], $subject, $message, $button_html);

        add_log('PASSWORD_RESET_REQUEST', "Wysłano link do resetowania hasła dla {$email}.", 'INFO', $user['id']);
    }
    
    // Zawsze pokazuj ten sam komunikat, aby nie ujawniać istnienia konta
    $_SESSION['success_message'] = 'Jeśli konto o podanym adresie e-mail istnieje, wysłaliśmy na nie link do resetowania hasła.';
    redirect('/reset_password');

} catch (Exception $e) {
    error_log("Błąd wysyłania resetu hasła: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd serwera. Spróbuj ponownie później.';
    redirect('/reset_password');
}

// Funkcja redirect musi być dostępna
if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}
