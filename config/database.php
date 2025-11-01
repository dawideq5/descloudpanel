<?php
// Plik: /config/database.php
$db_host = 'mysql8';
$db_user = '40197642_descloud';
$db_pass = 'r24hXiVT'; // Hasło z Twojego starego pliku
$db_name = '40197642_descloud';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Zapisz błąd do logów serwera
    error_log("Krytyczny błąd bazy danych: " . $e->getMessage());
    // Wyświetl prosty komunikat
    die("Błąd krytyczny: Nie można połączyć się z bazą danych. Skontaktuj się z administratorem.");
}