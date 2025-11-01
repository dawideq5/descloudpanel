<?php
// Plik: /config/boot.php

// === POPRAWKA KRYTYCZNA: Konfiguracja ścieżki sesji ===
// Ten błąd (Failed to read session data: files (path: /tmp))
// jest spowodowany tym, że serwer (np. home.pl) blokuje domyślną
// ścieżkę /tmp. Musimy ręcznie ustawić ścieżkę do naszego folderu.
ini_set('session.save_path', __DIR__ . '/../storage/sessions');
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
// === Koniec poprawki ===


// Podstawowa konfiguracja i autoloading

// Ustaw strefę czasową
date_default_timezone_set('Europe/Warsaw');

// Stałe Aplikacji
define('APP_URL', 'https://des.descloud.pl');
define('APP_NAME', 'descloud');

// Klucz szyfrowania (MUSI być 32-bajtowy, zakodowany w base64)
// Wygenerowany: base64_encode(random_bytes(32))
define('ENCRYPTION_KEY', 'TwójKluczSzyfrowaniaBase64Tutaj'); // TODO: Zmień to!

// Ładuj autoloadera composera
require_once __DIR__ . '/../vendor/autoload.php';

// Ładuj konfigurację bazy danych
require_once __DIR__ . '/database.php';

// === POPRAWKA (BŁĄD MAILERS): Ładowanie konfiguracji mailera ===
require_once __DIR__ . '/mailer.php';
// === KONIEC POPRAWKI ===

// Globalny error handling (prosty)
ini_set('display_errors', 1); // Wyłącz na produkcji
ini_set('display_startup_errors', 1); // Wyłącz na produkcji
error_reporting(E_ALL);
// NA KONIEC PLIKU NIE MA NIC WIĘCEJ.