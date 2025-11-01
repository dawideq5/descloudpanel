<?php
// Plik: /src/auth.php
require_once __DIR__ . '/../config/boot.php';

// Uruchom sesję, jeśli jeszcze nie jest aktywna
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Szyfruje dane
 * @param string $data Dane do zaszyfrowania
 * @return string Zaszyfrowany ciąg (base64)
 * @throws Exception
 */
function encrypt(string $data): string {
    $key = base64_decode(ENCRYPTION_KEY);
    $nonce = random_bytes(SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES);
    $ciphertext = sodium_crypto_aead_aes256gcm_encrypt($data, $nonce, $nonce, $key);
    return base64_encode($nonce . $ciphertext);
}

/**
 * Odszyfrowuje dane
 * @param string $data Zaszyfrowany ciąg (base64)
 * @return string Odszyfrowane dane
 * @throws Exception
 */
function decrypt(string $data): string {
    $key = base64_decode(ENCRYPTION_KEY);
    $decoded = base64_decode($data);
    $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES, '8bit');
    $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES, null, '8bit');
    return sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $nonce, $nonce, $key);
}

// === POPRAWKA KRYTYCZNA: Błędna ścieżka do pliku functions.php ===
// Było: require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/functions.php';

/**
 * Przekierowuje użytkownika pod inny URL.
 * @param string $url
 */
function redirect(string $url) {
    header('Location: ' . $url);
    session_write_close(); // Zapisz sesję przed przekierowaniem
    exit;
}

/**
 * Sprawdza, czy użytkownik jest zalogowany.
 * @return bool
 */
function is_logged_in(): bool {
    global $pdo;
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }

    // Dodatkowa weryfikacja z bazą danych (dla usunięcia sesji)
    $stmt = $pdo->prepare("SELECT session_token FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $db_token = $stmt->fetchColumn();

    if ($db_token === null || $db_token !== $_SESSION['session_token']) {
        session_destroy();
        return false;
    }

    return true;
}

/**
 * Wymaga zalogowania; jeśli nie, przekierowuje na /login.
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('/login');
    }
}