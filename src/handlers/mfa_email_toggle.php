<?php
// Plik: /src/handlers/mfa_email_toggle.php

if (!is_logged_in()) {
    redirect('/login');
}

global $pdo;
$user_id = $_SESSION['user_id'];
$action_type = $_POST['action_type'] ?? null; // 'enable' lub 'disable'
$password = $_POST['password'] ?? null;
$mfa_code = $_POST['mfa_code'] ?? null;

// Pobierz dane użytkownika
// === POPRAWKA KRTYTYCZNA: Używamy aliasu 'password AS password_hash' ===
$stmt = $pdo->prepare("SELECT email, password AS password_hash FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = 'Nie znaleziono użytkownika.';
    redirect('/settings?tab=mfa');
}

try {
    if ($action_type === 'enable') {
        // --- WŁĄCZANIE MFA E-MAIL (ZMIENIONA LOGIKA: TYLKO KOD) ---
        
        if (empty($mfa_code)) {
            // Krok 1: Wyślij kod, jeśli nie został podany
            $code = random_int(100000, 999999);
            $_SESSION['mfa_email_code'] = password_hash($code, PASSWORD_DEFAULT);
            $_SESSION['mfa_email_code_expires'] = time() + 300; // 5 minut

            $subject = 'Twój kod weryfikacyjny 2FA';
            $body = "Twój kod do włączenia weryfikacji e-mail to: <h2>$code</h2> Ten kod jest ważny przez 5 minut.";
            
            // send_email wyrzuci wyjątek, jeśli zawiedzie.
            send_email($user['email'], $subject, $body);
            
            $_SESSION['info_message'] = 'Wysłaliśmy kod na Twój adres e-mail. Wpisz go poniżej, aby aktywować metodę.';
            $_SESSION['show_mfa_email_code_input'] = true; 
            redirect('/settings?tab=mfa#mfa-email-card');
            
        } else {
            // Krok 2: Zweryfikuj kod i aktywuj
            if (!isset($_SESSION['mfa_email_code']) || !isset($_SESSION['mfa_email_code_expires'])) {
                throw new Exception('Sesja kodu wygasła. Poproś o nowy kod.');
            }
            if (time() > $_SESSION['mfa_email_code_expires']) {
                unset($_SESSION['mfa_email_code']);
                unset($_SESSION['mfa_email_code_expires']);
                unset($_SESSION['show_mfa_email_code_input']);
                throw new Exception('Kod weryfikacyjny wygasł. Poproś o nowy kod.');
            }
            if (!password_verify($mfa_code, $_SESSION['mfa_email_code'])) {
                throw new Exception('Nieprawidłowy kod weryfikacyjny.');
            }

            // Sukces - włącz MFA
            $stmt_enable = $pdo->prepare("UPDATE users SET mfa_email_enabled = 1 WHERE id = ?");
            $stmt_enable->execute([$user_id]);
            
            unset($_SESSION['mfa_email_code']);
            unset($_SESSION['mfa_email_code_expires']);
            unset($_SESSION['show_mfa_email_code_input']);
            
            add_log('MFA_EMAIL_ENABLED', 'Weryfikacja 2FA e-mail została włączona.', 'INFO');
            $_SESSION['success_message'] = 'Weryfikacja 2FA przez e-mail została włączona.';
            redirect('/settings?tab=mfa');
        }

    } elseif ($action_type === 'disable') {
        // --- WYŁĄCZANIE MFA E-MAIL (NADAL WYMAGA HASŁA) ---
        
        if (!password_verify($password, $user['password_hash'])) {
            add_log('MFA_EMAIL_TOGGLE_FAIL', "Nieudana próba wyłączenia MFA e-mail (błędne hasło).", 'WARNING');
            $_SESSION['error_message'] = 'Błędne hasło.';
            redirect('/settings?tab=mfa');
        }
        
        $stmt_disable = $pdo->prepare("UPDATE users SET mfa_email_enabled = 0 WHERE id = ?");
        $stmt_disable->execute([$user_id]);
        
        add_log('MFA_EMAIL_DISABLED', 'Weryfikacja 2FA e-mail została wyłączona.', 'INFO');
        $_SESSION['success_message'] = 'Weryfikacja 2FA przez e-mail została wyłączona.';
        redirect('/settings?tab=mfa');

    } else {
        throw new Exception('Nieznana akcja.');
    }

} catch (\PHPMailer\PHPMailer\Exception $e) {
    // Wyświetlenie konkretnego błędu PHPMailera
    error_log("Błąd PHPMailer: " . $e->getMessage());
    $_SESSION['error_message'] = 'BŁĄD WYSYŁKI E-MAILA: ' . $e->getMessage();
    redirect('/settings?tab=mfa#mfa-email-card');

} catch (Exception $e) {
    // Błąd logiki lub błąd ogólny
    error_log("Błąd przełączania MFA e-mail: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd: ' . $e->getMessage();
    redirect('/settings?tab=mfa#mfa-email-card');
}