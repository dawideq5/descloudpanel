<?php
// Plik: /src/handlers/settings_password_reset.php
// Publiczny
require_once __DIR__ . '/../config/boot.php';

// === POPRAWKA KRYTYCZNA: Błędna ścieżka do pliku functions.php ===
// Było: require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;
$token = $_POST['token'] ?? null;
$password = $_POST['password'] ?? null;
$password_confirm = $_POST['password_confirm'] ?? null;

if (!$token || !$password || !$password_confirm) {
    $_SESSION['error_message'] = 'Wszystkie pola są wymagane.';
    redirect('/reset_password?token=' . $token);
}

if ($password !== $password_confirm) {
    $_SESSION['error_message'] = 'Hasła nie są zgodne.';
    redirect('/reset_password?token=' . $token);
}

if (strlen($password) < 10) {
    $_SESSION['error_message'] = 'Hasło musi mieć co najmniej 10 znaków.';
    redirect('/reset_password?token=' . $token);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error_message'] = 'Link do resetowania hasła jest nieprawidłowy lub wygasł.';
        redirect('/reset_password');
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt_update = $pdo->prepare(
        "UPDATE users SET 
            password_hash = ?, 
            password_reset_token = NULL, 
            password_reset_expires = NULL,
            session_token = NULL -- Wyloguj ze wszystkich sesji
         WHERE id = ?"
    );
    $stmt_update->execute([$password_hash, $user['id']]);

    add_log('PASSWORD_RESET_SUCCESS', 'Hasło zostało zresetowane pomyślnie.', 'INFO', $user['id']);

    $_SESSION['success_message'] = 'Twoje hasło zostało zmienione. Możesz się teraz zalogować.';
    redirect('/login');

} catch (Exception $e) {
    error_log("Błąd resetowania hasła: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd serwera.';
    redirect('/reset_password?token=' . $token);
}