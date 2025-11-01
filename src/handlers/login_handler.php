<?php
// Plik: /src/handlers/login_handler.php
// Wersja 5 - Dodano przekierowanie do MFA

require_once __DIR__ . '/../auth.php'; // Ładuje boot.php, $pdo, sesję, functions.php

$username = $_POST['username'] ?? ''; // To jest adres e-mail, ale nazwa pola to 'username'
$password = $_POST['password'] ?? ''; // Hasło z formularza
$user_ip = $_SERVER['REMOTE_ADDR'];

// Bezpieczeństwo: Sprawdzanie Brute Force
$max_attempts = 5;
$lockout_time = 300; // 5 minut

try {
    global $pdo;
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
    $stmt_check->execute([$user_ip, time() - $lockout_time]);
    if ($stmt_check->fetchColumn() >= $max_attempts) {
        header('Location: /login?error=locked');
        exit;
    }

    // === POPRAWKA KRTYTYCZNA: Używamy kolumny 'email' do logowania i poprawnie wybieramy 'password_hash' ===
    // Wcześniejsze: "SELECT id, password AS password_hash, user_type_id, mfa_totp_enabled, mfa_email_enabled FROM users WHERE email = ?"
    $stmt = $pdo->prepare("SELECT id, password_hash, user_type_id, mfa_totp_enabled, mfa_email_enabled FROM users WHERE email = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    // Hasło jest teraz dostępne pod kluczem `$user['password_hash']`, tak jak oczekuje reszta kodu.

    if ($user && password_verify($password, trim($user['password_hash']))) {
        // SUKCES HASŁA
        
        // Sprawdź, czy ma klucze WebAuthn
        $stmt_webauthn = $pdo->prepare("SELECT COUNT(*) FROM webauthn_credentials WHERE user_id = ?");
        $stmt_webauthn->execute([$user['id']]);
        $webauthn_enabled = $stmt_webauthn->fetchColumn() > 0;

        $mfa_required = $user['mfa_totp_enabled'] || $user['mfa_email_enabled'] || $webauthn_enabled;
        
        // --- POPRAWKA: Określ i zapisz dostępne metody MFA ---
        $mfa_methods = [];
        if ($user['mfa_totp_enabled']) {
            $mfa_methods[] = 'totp';
        }
        if ($user['mfa_email_enabled']) {
            $mfa_methods[] = 'email';
        }
        if ($webauthn_enabled) {
            $mfa_methods[] = 'webauthn';
        }
        // --- KONIEC POPRAWKI ---

        if ($mfa_required) {
            // Logowanie wymaga drugiego etapu
            session_regenerate_id(true); // Zabezpiecz sesję
            $_SESSION['mfa_pending_user_id'] = $user['id'];
            $_SESSION['mfa_pending_username'] = $username;
            $_SESSION['mfa_pending_user_type_id'] = $user['user_type_id'];
            $_SESSION['mfa_action_required'] = 'login'; // Cel: logowanie
            $_SESSION['mfa_methods'] = $mfa_methods; // <--- DODANIE DO SESJI
            
            // Zapisz log o pierwszej fazie
            add_log('AUTH_MFA_REQUIRED', 'Użytkownik podał poprawne hasło, wymagane MFA.', 'INFO', $user['id']);
            
            header('Location: /mfa-verify');
            exit;

        } else {
            // Logowanie BEZ MFA (stara logika)
            $stmt_del = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt_del->execute([$user_ip]);

            session_regenerate_id(true);
            $session_token = bin2hex(random_bytes(32));

            // Zaktualizowano: last_login i session_token
            $stmt_update = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP, session_token = ? WHERE id = ?");
            $stmt_update->execute([$session_token, $user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['user_type_id'] = $user['user_type_id'];
            $_SESSION['session_token'] = $session_token;
            unset($_SESSION['permissions']); 

            add_log('AUTH_LOGIN_SUCCESS', 'Użytkownik zalogował się pomyślnie (bez MFA).', 'INFO', $user['id']);

            header('Location: /dashboard');
            exit;
        }

    } else {
        // BŁĄD LOGOWANIA
        $stmt_ins = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, ?)");
        $stmt_ins->execute([$user_ip, time()]);
        
        $failed_user_id = $user ? $user['id'] : null;
        add_log('AUTH_LOGIN_FAILURE', 'Nieudana próba logowania dla użytkownika: ' . $username, 'WARNING', $failed_user_id);
        
        header('Location: /login?error=1');
        exit;
    }

} catch (PDOException $e) {
    // === POPRAWKA: Wyświetlanie błędu bazy danych w trybie deweloperskim ===
    if (ini_get('display_errors')) {
        error_log("Błąd logowania (PDO): " . $e->getMessage());
        add_log('DATABASE_ERROR', 'Krytyczny błąd bazy danych podczas logowania: ' . $e->getMessage(), 'CRITICAL', null);
        // Zatrzymaj aplikację i wyświetl błąd krytyczny dla dewelopera
        die("Krytyczny błąd bazy danych (zobacz error_log): " . $e->getMessage());
    } else {
        error_log("Błąd logowania (PDO): " . $e->getMessage());
        add_log('DATABASE_ERROR', 'Krytyczny błąd bazy danych podczas logowania.', 'ERROR', null);
        header('Location: /login?error=db');
        exit;
    }
}