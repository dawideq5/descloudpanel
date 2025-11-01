<?php
// Plik: /src/handlers/mfa_totp_disable.php

// Plik /src/auth.php jest już załadowany przez index.php, 
// więc funkcje redirect() i is_logged_in() są dostępne.
// Nie trzeba dołączać /../auth.php

// Musimy załadować autoloadera, aby użyć biblioteki RobThree
require_once __DIR__ . '/../../vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;

if (!is_logged_in()) {
    redirect('/login');
}

global $pdo;
$user_id = $_SESSION['user_id'];
$password = $_POST['password'] ?? null;
// $totp_code = $_POST['totp_code'] ?? null; // Usunięte zgodnie z prośbą

if (!$password) {
    $_SESSION['error_message'] = 'Musisz podać swoje hasło, aby wyłączyć TOTP.';
    redirect('/settings?tab=mfa');
}

try {
    // 1. Pobierz użytkownika
    // POPRAWKA: Używamy 'password AS password_hash', aby pobrać właściwy hash.
    $stmt = $pdo->prepare("SELECT password AS password_hash, mfa_totp_secret, mfa_totp_enabled FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Nie znaleziono użytkownika.');
    }

    // 2. Sprawdź hasło
    // $user['password_hash'] zawiera teraz wartość z kolumny 'password'
    if (!password_verify($password, $user['password_hash'])) {
        add_log('MFA_TOTP_DISABLE_FAIL', 'Nieudana próba wyłączenia TOTP (błędne hasło).', 'WARNING');
        $_SESSION['error_message'] = 'Błędne hasło.';
        redirect('/settings?tab=mfa');
    }

    // === ZMIANA: USUNIĘTO WERYFIKACJĘ KODU TOTP ===
    // Weryfikacja kodu została usunięta zgodnie z prośbą. Wystarczy hasło.

    // 3. Wyłącz TOTP w bazie danych
    $stmt_disable = $pdo->prepare("UPDATE users SET mfa_totp_secret = NULL, mfa_totp_enabled = 0 WHERE id = ?");
    $stmt_disable->execute([$user_id]);

    add_log('MFA_TOTP_DISABLED', 'Weryfikacja TOTP została wyłączona.', 'INFO');
    $_SESSION['success_message'] = 'Aplikacja uwierzytelniająca (TOTP) została pomyślnie wyłączona.';
    redirect('/settings?tab=mfa');

} catch (Exception $e) {
    error_log("Błąd wyłączania TOTP: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd serwera: ' . $e->getMessage();
    redirect('/settings?tab=mfa');
}