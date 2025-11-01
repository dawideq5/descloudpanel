<?php
// Plik: /src/handlers/register_handler.php
// Wersja 2 - Ulepszone komunikaty błędów i walidacja

require_once __DIR__ . '/../auth.php';

if (is_logged_in()) {
    redirect('/dashboard');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit('Niedozwolona metoda żądania.');
}

// Pobierz dane z formularza
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// --- Ulepszona Walidacja ---
$errors = [];
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    $errors[] = 'Wszystkie pola są wymagane.';
}
if ($password !== $password_confirm) {
    $errors[] = 'Hasła nie są identyczne.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Podany adres e-mail jest nieprawidłowy.';
}
// Można dodać więcej walidacji, np. długość hasła

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    // Zachowaj wprowadzone dane, aby użytkownik nie musiał wpisywać od nowa
    $_SESSION['form_data'] = $_POST;
    redirect('/register');
}
// --- Koniec Walidacji ---

global $pdo;

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error_message'] = 'Ten adres e-mail jest już zarejestrowany.';
        $_SESSION['form_data'] = $_POST;
        redirect('/register');
    }

    $username = generate_username($first_name, $last_name);
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $verification_token = bin2hex(random_bytes(32));
    $verification_expires = date('Y-m-d H:i:s', time() + 900); // 15 minut

    $sql = "INSERT INTO users (user_type_id, username, email, password_hash, first_name, last_name, is_active, email_verification_token, email_verification_expires)
            VALUES (:user_type_id, :username, :email, :password_hash, :first_name, :last_name, :is_active, :email_verification_token, :email_verification_expires)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_type_id' => 2,
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $password_hash,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':is_active' => 0,
        ':email_verification_token' => $verification_token,
        ':email_verification_expires' => $verification_expires
    ]);

    $user_id = $pdo->lastInsertId();
    add_log('AUTH_REGISTER_SUCCESS', 'Nowy użytkownik zarejestrowany: ' . $email, 'INFO', $user_id);

    // TODO: Wysyłka e-maila weryfikacyjnego powinna być tutaj

    $_SESSION['success_message'] = 'Rejestracja zakończona pomyślnie! Na Twój adres e-mail wysłaliśmy link weryfikacyjny.';
    redirect('/login');

} catch (PDOException $e) {
    error_log("Błąd rejestracji (PDO): " . $e->getMessage());
    add_log('DATABASE_ERROR', 'Krytyczny błąd bazy danych podczas rejestracji.', 'CRITICAL', null);
    $_SESSION['error_message'] = 'Wystąpił błąd serwera podczas próby rejestracji. Spróbuj ponownie później.';
    redirect('/register');
}
