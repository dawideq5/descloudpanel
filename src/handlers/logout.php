<?php
// Plik: /src/handlers/logout.php
require_once __DIR__ . '/../auth.php'; // Ładuje $pdo i sesję

if (isset($_SESSION['user_id'])) {
    try {
        global $pdo;
        // Wyczyść token sesji w bazie
        $stmt = $pdo->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        add_log('AUTH_LOGOUT', 'Użytkownik wylogował się.', 'INFO', $_SESSION['user_id']);

    } catch (PDOException $e) {
        error_log("Błąd podczas wylogowywania (DB update): " . $e->getMessage());
    }
}

// Zniszcz sesję
session_unset();
session_destroy();

// Przekieruj do logowania
header('Location: /login?logged_out=1');
exit;