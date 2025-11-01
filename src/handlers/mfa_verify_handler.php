<?php
// Plik: /src/handlers/mfa_verify_handler.php
// Wersja 2 - Ulepszone komunikaty błędów

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../config/mailer.php';

use RobThree\Auth\TwoFactorAuth;

if (!isset($_SESSION['mfa_pending_user_id'])) {
    $_SESSION['error_message'] = 'Twoja sesja wygasła. Zaloguj się ponownie.';
    redirect('/login');
}

global $pdo;
$user_id = $_SESSION['mfa_pending_user_id'];
$action_on_success = $_SESSION['mfa_action_required'] ?? 'login';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Użytkownik nie istnieje.');
    }

    if (isset($_GET['resend'])) {
        $result = sendEmailVerificationCode($user);
        if ($user['mfa_email_enabled']) {
            if ($result['success']) {
                $_SESSION['success_message'] = 'Nowy kod weryfikacyjny został wysłany na Twój adres e-mail.';
                add_log('MFA_EMAIL_CODE_RESENT', 'Ponownie wysłano kod MFA na e-mail.', 'INFO', $user_id);
            } else {
                $_SESSION['error_message'] = 'Błąd wysyłki e-mail. Szczegóły: ' . ($result['error'] ?? 'Nieznany błąd.');
                add_log('MFA_EMAIL_SEND_FAILED', 'Błąd wysyłki kodu MFA na e-mail: ' . ($result['error'] ?? 'Brak szczegółów.'), 'ERROR', $user_id);
            }
        } else {
            $_SESSION['error_message'] = 'Metoda weryfikacji e-mail nie jest włączona.';
        }
        redirect('/mfa-verify');
    }

    $method = $_POST['method'] ?? null;

    if ($method === 'totp' && $user['mfa_totp_enabled']) {
        $code = $_POST['totp_code'] ?? '';
        $tfa = new TwoFactorAuth('Descloud');
        
        if ($tfa->verifyCode($user['mfa_totp_secret'], $code)) {
            handleMfaSuccess();
        } else {
            add_log('AUTH_MFA_FAILURE', 'Podano błędny kod TOTP.', 'WARNING', $user_id);
            $_SESSION['error_message'] = 'Błędny kod TOTP. Spróbuj ponownie.';
            redirect('/mfa-verify');
        }
    } elseif ($method === 'email_code' && $user['mfa_email_enabled']) {
        $code = $_POST['email_code'] ?? '';
        
        if (verifyEmailCode($user, $code)) {
            $stmt_clear = $pdo->prepare("UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $stmt_clear->execute([$user_id]);
            handleMfaSuccess();
        } else {
            add_log('AUTH_MFA_FAILURE', 'Podano błędny kod E-mail.', 'WARNING', $user_id);
            $_SESSION['error_message'] = 'Błędny kod e-mail lub kod wygasł. Spróbuj ponownie.';
            redirect('/mfa-verify');
        }
    } elseif ($method === 'email_send' && $user['mfa_email_enabled']) { 
        $result = sendEmailVerificationCode($user);
        if ($result['success']) {
            $_SESSION['success_message'] = 'Kod weryfikacyjny został wysłany na Twój adres e-mail.';
            add_log('MFA_EMAIL_CODE_SENT', 'Wysłano kod MFA na e-mail.', 'INFO', $user_id);
        } else {
            $_SESSION['error_message'] = 'Błąd wysyłki e-mail. Szczegóły: ' . ($result['error'] ?? 'Nieznany błąd.');
            add_log('MFA_EMAIL_SEND_FAILED', 'Błąd wysyłki kodu MFA na e-mail: ' . ($result['error'] ?? 'Brak szczegółów.'), 'ERROR', $user_id);
        }
        redirect('/mfa-verify');
    } else {
        add_log('AUTH_MFA_FAILURE', 'Próba weryfikacji MFA z nieznaną/wyłączoną metodą.', 'ERROR', $user_id);
        $_SESSION['error_message'] = 'Nieprawidłowa metoda weryfikacji.';
        redirect('/mfa-verify');
    }
} catch (Exception $e) {
    error_log("Błąd weryfikacji MFA: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił krytyczny błąd serwera. Zaloguj się ponownie.';
    redirect('/login');
}

// Reszta funkcji (handleMfaSuccess, sendEmailVerificationCode, verifyEmailCode) pozostaje bez zmian...
