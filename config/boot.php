<?php
// Plik: /config/boot.php
// Wersja 2 - Ładowanie zmiennych środowiskowych z .env

// Ładuj autoloadera composera
require_once __DIR__ . '/../vendor/autoload.php';

// Ładuj zmienne środowiskowe z pliku .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// === POPRAWKA KRYTYCZNA: Konfiguracja ścieżki sesji ===
ini_set('session.save_path', __DIR__ . '/../storage/sessions');
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
// === Koniec poprawki ===

// Podstawowa konfiguracja i autoloading

// Ustaw strefę czasową
date_default_timezone_set('Europe/Warsaw');

// Ładuj nową konfigurację i połączenie z bazą danych
require_once __DIR__ . '/../src/db.php';

// Ładuj konfigurację mailera
require_once __DIR__ . '/mailer.php';

// Globalny error handling jest teraz w config.php
