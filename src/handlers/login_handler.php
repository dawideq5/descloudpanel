<?php
// Plik: /src/handlers/login_handler.php
// Wersja 7 - Ulepszone komunikaty błędów

require_once __DIR__ . '/../auth.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$user_ip = $_SERVER['REMOTE_ADDR'];

// Bezpieczeństwo: Sprawdzanie Brute Force
$max_attempts = 5;
$lockout_time = 300; // 5 minut

try {
    global $pdo;
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
    $stmt_check->execute([$user_ip, time() - $lockout_time]);
    if ($stmt_check->fetchColumn() >= $max_attempts) {
        $_SESSION['error_message'] = 'Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za 5 minut.';
        redirect('/login');
    }

    $stmt = $pdo->prepare(
        "SELECT id, password_hash, user_type_id, is_active, mfa_totp_enabled, mfa_email_enabled, first_name, last_name
         FROM users
         WHERE username = :login OR email = :login OR phone_number = :login"
    );
    $stmt->execute(['login' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, trim($user['password_hash']))) {
        if ($user['is_active'] != 1) {
            add_log('AUTH_LOGIN_FAILURE', 'Próba logowania na nieaktywne konto: ' . $username, 'WARNING', $user['id']);
            $_SESSION['error_message'] = 'Twoje konto nie jest aktywne. Sprawdź swoją skrzynkę e-mail w celu weryfikacji lub skontaktuj się z administratorem.';
            redirect('/login');
        }

        // Reszta logiki logowania (MFA lub bezpośrednie) pozostaje bez zmian...
        // ... (kod pominięty dla zwięzłości)

    } else {
        // BŁĄD LOGOWANIA
        $stmt_ins = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, ?)");
        $stmt_ins->execute([$user_ip, time()]);
        
        $failed_user_id = $user ? $user['id'] : null;
        add_log('AUTH_LOGIN_FAILURE', 'Nieudana próba logowania dla: ' . htmlspecialchars($username), 'WARNING', $failed_user_id);
        
        $_SESSION['error_message'] = 'Nieprawidłowy login lub hasło.';
        redirect('/login');
    }

} catch (PDOException $e) {
    add_log('DATABASE_ERROR', 'Krytyczny błąd bazy danych podczas logowania.', 'CRITICAL', null);
    error_log("Błąd logowania (PDO): " . $e->getMessage());

    $_SESSION['error_message'] = 'Wystąpił błąd serwera. Spróbuj ponownie później.';
    redirect('/login');
}
