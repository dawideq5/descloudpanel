<?php
// Plik: /src/handlers/mfa_verify_handler.php
// Obsługuje weryfikację kodów TOTP i E-mail z /mfa-verify

require_once __DIR__ . '/../auth.php'; // Ładuje boot.php, $pdo, sesję, functions.php
require_once __DIR__ . '/../../config/mailer.php'; // Dla wysyłki kodu e-mail

use RobThree\Auth\TwoFactorAuth;

if (!isset($_SESSION['mfa_pending_user_id'])) {
    // POPRAWKA: Używamy błędu session_expired, który jest teraz obsługiwany w login.php
    header('Location: /login?error=session_expired'); 
    exit;
}

global $pdo;
$user_id = $_SESSION['mfa_pending_user_id'];
$action_on_success = $_SESSION['mfa_action_required'] ?? 'login';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) throw new Exception('Użytkownik nie istnieje.');

    // === Obsługa ponownego wysłania kodu e-mail ===
    if (isset($_GET['resend'])) {
        // Zmieniono logikę obsługi zwrotu z funkcji
        $result = sendEmailVerificationCode($user);
        if ($user['mfa_email_enabled']) {
            if ($result['success']) {
                $_SESSION['success_message'] = 'Nowy kod weryfikacyjny został wysłany na Twój adres e-mail.';
                add_log('MFA_EMAIL_CODE_RESENT', 'Ponownie wysłano kod MFA na e-mail.', 'INFO', $user_id);
            } else {
                // POPRAWKA: Przekazanie szczegółowego błędu
                $_SESSION['error_message'] = 'Błąd wysyłki e-mail. Szczegóły: ' . ($result['error'] ?? 'Nieznany błąd PHPMailer.');
                add_log('MFA_EMAIL_SEND_FAILED', 'Błąd wysyłki kodu MFA na e-mail: ' . ($result['error'] ?? 'Brak szczegółów.'), 'ERROR', $user_id);
            }
        } else {
            $_SESSION['error_message'] = 'Metoda weryfikacji e-mail nie jest włączona.';
        }
        header('Location: /mfa-verify');
        exit;
    }

    // === Weryfikacja metody ===
    $method = $_POST['method'] ?? null;
    $error_redirect = '/mfa-verify'; 

    if ($method === 'totp' && $user['mfa_totp_enabled']) {
        $code = $_POST['totp_code'] ?? '';
        $tfa = new TwoFactorAuth('Descloud');
        
        if ($tfa->verifyCode($user['mfa_totp_secret'], $code)) {
            handleMfaSuccess();
        } else {
            add_log('AUTH_MFA_FAILURE', 'Podano błędny kod TOTP.', 'WARNING', $user_id);
            $_SESSION['error_message'] = 'Błędny kod TOTP. Spróbuj ponownie.';
            header('Location: ' . $error_redirect);
            exit;
        }

    } elseif ($method === 'email_code' && $user['mfa_email_enabled']) {
        $code = $_POST['email_code'] ?? '';
        
        if (verifyEmailCode($user, $code)) {
            $stmt_clear = $pdo->prepare("UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $stmt_clear->execute([$user_id]);

            handleMfaSuccess();
        } else {
            add_log('AUTH_MFA_FAILURE', 'Podano błędny kod E-mail.', 'WARNING', $user_id);
            $_SESSION['error_message'] = 'Błędny kod e-mail lub kod wygasł. Spróbuj ponownie wysłać kod.'; 
            header('Location: ' . $error_redirect);
            exit;
        }

    } elseif ($method === 'email_send' && $user['mfa_email_enabled']) { 
        // Zmieniono logikę obsługi zwrotu z funkcji
        $result = sendEmailVerificationCode($user);
        if ($result['success']) {
            $_SESSION['mfa_email_sent'] = true; 
            $_SESSION['success_message'] = 'Kod weryfikacyjny został wysłany na Twój adres e-mail.';
            add_log('MFA_EMAIL_CODE_SENT', 'Wysłano kod MFA na e-mail.', 'INFO', $user_id);
        } else {
            // POPRAWKA: Przekazanie szczegółowego błędu
            $_SESSION['error_message'] = 'Błąd wysyłki e-mail. Szczegóły: ' . ($result['error'] ?? 'Nieznany błąd PHPMailer.');
            add_log('MFA_EMAIL_SEND_FAILED', 'Błąd wysyłki kodu MFA na e-mail: ' . ($result['error'] ?? 'Brak szczegółów.'), 'ERROR', $user_id);
        }
        header('Location: /mfa-verify');
        exit;

    } else {
        // Nieznana metoda lub próba oszustwa
        add_log('AUTH_MFA_FAILURE', 'Próba weryfikacji MFA z nieznaną/wyłączoną metodą.', 'ERROR', $user_id);
        $_SESSION['error_message'] = 'Nieprawidłowa próba weryfikacji. Wyloguj się i spróbuj ponownie.'; 
        header('Location: ' . $error_redirect);
        exit;
    }

} catch (Exception $e) {
    error_log("Błąd weryfikacji MFA: " . $e->getMessage());
    header('Location: /login?error=db'); 
    exit;
}


/**
 * Logika wykonywana po udanej weryfikacji MFA (dowolną metodą).
 * ... (pozostała część funkcji handleMfaSuccess bez zmian)
 */
function handleMfaSuccess() {
    global $pdo, $user_id, $action_on_success;
    
    add_log('AUTH_MFA_SUCCESS', 'Pomyślna weryfikacja dwuetapowa.', 'INFO', $user_id);

    // Na podstawie akcji, podejmij decyzję
    switch ($action_on_success) {
        case 'reset_password':
            // Weryfikacja była po to, by wysłać link resetu hasła.
            // Zaloguj tymczasowo, aby handler resetu mógł działać
            $_SESSION['user_id'] = $_SESSION['mfa_pending_user_id'];
            $_SESSION['username'] = $_SESSION['mfa_pending_username'];
            
            // Wyczyść stan "pending"
            unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_pending_username'], $_SESSION['mfa_action_required']);

            // Przekieruj do handlera, który teraz wyśle e-mail
            header('Location: /settings/password/request?mfa_verified=1');
            exit;
            
        case 'disable_totp':
            // Weryfikacja była po to, by wyłączyć TOTP
            $_SESSION['user_id'] = $_SESSION['mfa_pending_user_id'];
            $_SESSION['username'] = $_SESSION['mfa_pending_username'];
            unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_pending_username'], $_SESSION['mfa_action_required']);
            header('Location: /settings/mfa/totp/disable?mfa_verified=1');
            exit;
            
        case 'login':
        default:
            // Standardowe logowanie
            $username = $_SESSION['mfa_pending_username'];
            $user_type_id = $_SESSION['mfa_pending_user_type_id'];

            // Wyczyść stare próby Brute Force
            $stmt_del = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt_del->execute([$_SERVER['REMOTE_ADDR']]);

            // Wygeneruj finalną, pełną sesję
            $session_token = bin2hex(random_bytes(32));
            $stmt_update = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP, session_token = ? WHERE id = ?");
            $stmt_update->execute([$session_token, $user_id]);

            // Ustaw pełną sesję
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_type_id'] = $user_type_id;
            $_SESSION['session_token'] = $session_token;
            unset($_SESSION['permissions']); 

            // Wyczyść stan "pending"
            unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_pending_username'], $_SESSION['mfa_pending_user_type_id'], $_SESSION['mfa_action_required']);

            add_log('AUTH_LOGIN_SUCCESS', 'Użytkownik zalogował się pomyślnie (z MFA).', 'INFO', $user_id);
            header('Location: /dashboard');
            exit;
    }
}

/**
 * Wysyła 6-cyfrowy kod na e-mail użytkownika i zapisuje go w bazie.
 * Zwraca tablicę: ['success' => bool, 'error' => string|null]
 */
function sendEmailVerificationCode(array $user): array {
    global $pdo;
    try {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', time() + 900); // 15 minut

        // Zapisz kod (jako hash) w polu tokenu resetowania hasła
        $hashed_code = password_hash($code, PASSWORD_DEFAULT);
        $stmt_update = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
        $stmt_update->execute([$hashed_code, $expires_at, $user['id']]);

        // Wyślij e-mail
        $mail = getMailer();
        // POPRAWKA: Ustawiamy format na Plain Text
        $mail->isHTML(false);
        $mail->ContentType = 'text/plain';

        if (!$mail) {
            // Ten warunek jest osiągany, jeśli getMailer zwróci false (w config/mailer.php)
            return ['success' => false, 'error' => 'Błąd inicjalizacji Mailera. Sprawdź config/mailer.php.'];
        }
        
        $mail->addAddress($user['email']);
        $mail->Subject = 'Twój kod weryfikacyjny - Descloud';
        $mail->Body    = "Witaj,\n\nTwój jednorazowy kod weryfikacyjny to: $code\n\nKod jest ważny przez 15 minut.";
        $mail->send();
        
        return ['success' => true, 'error' => null];
        
    } catch (Exception $e) {
        // Złapanie błędu PHPMailer (np. błąd połączenia SMTP, błąd autoryzacji)
        $error_info = $e->getMessage();
        if (isset($mail) && $mail->ErrorInfo) {
            $error_info = $mail->ErrorInfo; // Najczęściej zawiera szczegóły SMTP
        }
        error_log("Błąd wysyłania kodu e-mail MFA (Szczegóły): " . $error_info);
        
        return ['success' => false, 'error' => $error_info];
    }
}

/**
 * Weryfikuje 6-cyfrowy kod z e-maila.
 */
function verifyEmailCode(array $user, string $code): bool {
    if (empty($code)) return false;
    
    // Sprawdź, czy token nie wygasł i czy hash się zgadza
    if (strtotime($user['password_reset_expires']) > time() && password_verify($code, $user['password_reset_token'])) {
        return true;
    }
    return false;
}