<?php
// Plik: /src/handlers/settings_password_handler.php
// Nowy, brakujący handler do obsługi zmiany hasła

if (!is_logged_in()) {
    redirect('/login');
}

global $pdo;
$user_id = $_SESSION['user_id'];

// Pobierz dane z formularza
$password_current = $_POST['password_current'] ?? null;
$password_new = $_POST['password_new'] ?? null;
$password_confirm = $_POST['password_confirm'] ?? null;

// Walidacja
if (empty($password_current) || empty($password_new) || empty($password_confirm)) {
    $_SESSION['error_message'] = 'Wszystkie pola są wymagane.';
    redirect('/settings?tab=password');
}


if ($password_new !== $password_confirm) {
    $_SESSION['error_message'] = 'Nowe hasła nie są identyczne.';
    redirect('/settings?tab=password');
}

try {
    // 1. Sprawdź aktualne hasło
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password_current, $user['password_hash'])) {
        add_log('PASSWORD_CHANGE_FAIL', 'Nieudana próba zmiany hasła (błędne aktualne hasło).', 'WARNING');
        $_SESSION['error_message'] = 'Aktualne hasło jest nieprawidłowe.';
        redirect('/settings?tab=password');
    }

    // 2. Zaktualizuj hasło
    $new_password_hash = password_hash($password_new, PASSWORD_DEFAULT);
    $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt_update->execute([$new_password_hash, $user_id]);

    add_log('PASSWORD_CHANGE_SUCCESS', 'Hasło zostało pomyślnie zmienione.', 'INFO');
    $_SESSION['success_message'] = 'Hasło zostało pomyślnie zmienione.';
    redirect('/settings?tab=password');

} catch (PDOException $e) {
    error_log("Błąd zmiany hasła: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd bazy danych podczas zmiany hasła.';
    redirect('/settings?tab=password');
}