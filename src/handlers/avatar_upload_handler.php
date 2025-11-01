<?php
// Plik: /src/handlers/avatar_upload_handler.php
// Wersja 2 - Ulepszone komunikaty błędów

require_once __DIR__ . '/../auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Nieprawidłowe żądanie.';
    redirect('/settings?tab=avatar');
}

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    $upload_dir = __DIR__ . '/../../storage/avatars/';

    try {
        // Walidacja typu pliku
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Nieprawidłowy format pliku. Dozwolone formaty: JPG, PNG, GIF.');
        }

        // Walidacja rozmiaru (2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Plik jest zbyt duży. Maksymalny rozmiar to 2 MB.');
        }

        // Utwórz katalog, jeśli nie istnieje
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception('Nie można utworzyć katalogu do zapisu awatarów.');
        }

        // Generowanie unikalnej nazwy pliku
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Aktualizacja bazy danych
            $avatar_url = '/storage/avatars/' . $filename;

            global $pdo;
            $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
            $stmt->execute([$avatar_url, $_SESSION['user_id']]);

            $_SESSION['success_message'] = 'Awatar został pomyślnie zaktualizowany.';
            add_log('AVATAR_UPLOAD_SUCCESS', 'Użytkownik pomyślnie zmienił awatar.', 'INFO');
            redirect('/settings?tab=avatar');
        } else {
            throw new Exception('Nie udało się przenieść przesłanego pliku.');
        }
    } catch (Exception $e) {
        error_log("Błąd przesyłania awatara: " . $e->getMessage());
        $_SESSION['error_message'] = 'Wystąpił błąd podczas przesyłania pliku: ' . $e->getMessage();
        redirect('/settings?tab=avatar');
    }
} else {
    // Mapowanie kodów błędów na komunikaty
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'Plik przekracza maksymalny dozwolony rozmiar.',
        UPLOAD_ERR_FORM_SIZE  => 'Plik przekracza maksymalny dozwolony rozmiar.',
        UPLOAD_ERR_PARTIAL    => 'Plik został przesłany tylko częściowo.',
        UPLOAD_ERR_NO_FILE    => 'Nie wybrano żadnego pliku.',
        UPLOAD_ERR_NO_TMP_DIR => 'Brak katalogu tymczasowego na serwerze.',
        UPLOAD_ERR_CANT_WRITE => 'Nie można zapisać pliku na dysku.',
        UPLOAD_ERR_EXTENSION  => 'Rozszerzenie PHP zatrzymało przesyłanie pliku.',
    ];
    $error_code = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
    $_SESSION['error_message'] = $upload_errors[$error_code] ?? 'Wystąpił nieznany błąd przesyłania.';
    redirect('/settings?tab=avatar');
}
