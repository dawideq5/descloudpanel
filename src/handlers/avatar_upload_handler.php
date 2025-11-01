<?php
// Plik: /src/handlers/avatar_upload_handler.php

require_once __DIR__ . '/../auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/settings?tab=avatar&error=invalid_request');
}

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];

    // Walidacja typu pliku
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        redirect('/settings?tab=avatar&error=invalid_type');
    }

    // Walidacja rozmiaru (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        redirect('/settings?tab=avatar&error=too_large');
    }

    // Generowanie unikalnej nazwy pliku
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $_SESSION['user_id'] . '_' . uniqid() . '.' . $extension;

    // Ścieżka do zapisu
    $upload_dir = __DIR__ . '/../../storage/avatars/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Aktualizacja bazy danych
        $avatar_url = '/storage/avatars/' . $filename;

        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$avatar_url, $_SESSION['user_id']]);

        redirect('/settings?tab=avatar&success=uploaded');
    } else {
        redirect('/settings?tab=avatar&error=upload_failed');
    }
} else {
    redirect('/settings?tab=avatar&error=no_file');
}
