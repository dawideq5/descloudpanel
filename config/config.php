<?php
// Plik: /config/config.php
// Wersja 2 - Używa zmiennych środowiskowych

// Konfiguracja Bazy Danych
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_CHARSET', $_ENV['DB_CHARSET']);

// Klucz szyfrowania
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY']);

// Ustawienia aplikacji
define('APP_URL', $_ENV['APP_URL']);
define('APP_NAME', $_ENV['APP_NAME']);

// Tryb deweloperski
define('DEV_MODE', $_ENV['DEV_MODE'] === 'true');

if (DEV_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}
