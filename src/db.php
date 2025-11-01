<?php
// Plik: /src/db.php

// Ten plik jest odpowiedzialny za JEDNO zadanie:
// stworzenie i udostępnienie obiektu połączenia PDO.

require_once __DIR__ . '/../config/config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Zapisz błąd do logów serwera
    error_log("Krytyczny błąd bazy danych: " . $e->getMessage());

    // Wyświetl ogólny komunikat błędu, jeśli nie jesteśmy w trybie deweloperskim
    if (!defined('DEV_MODE') || !DEV_MODE) {
        die("Błąd krytyczny: Nie można połączyć się z bazą danych. Skontaktuj się z administratorem.");
    } else {
        // W trybie deweloperskim wyświetl szczegółowy błąd
        die("Błąd krytyczny bazy danych: " . $e->getMessage());
    }
}
