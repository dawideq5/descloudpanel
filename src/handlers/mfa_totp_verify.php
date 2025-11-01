<?php
// Plik: /src/handlers/mfa_totp_verify.php
// Wersja 2 - Ulepszone komunikaty błędów

require_once __DIR__ . '/../auth.php'; 
use RobThree\Auth\TwoFactorAuth;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in()) {
    redirect('/login');
}

global $pdo;
$user_id = $_SESSION['user_id'];
$code = $_POST['totp_code'] ?? '';
$secret = $_SESSION['mfa_setup_secret'] ?? null;

if (empty($code)) {
    $_SESSION['error_message'] = 'Musisz podać kod weryfikacyjny.';
    redirect('/settings?tab=mfa');
}

if (!$secret) {
    $_SESSION['error_message'] = 'Sesja konfiguracji MFA wygasła. Spróbuj ponownie wygenerować kod QR.';
    redirect('/settings?tab=mfa');
}

try {
    $tfa = new TwoFactorAuth('Descloud');
    
    if ($tfa->verifyCode($secret, $code)) {
        // Sukces! Zapisz sekret w bazie
        // TODO: W środowisku produkcyjnym ten sekret powinien być zaszyfrowany!
        $stmt = $pdo->prepare("UPDATE users SET mfa_totp_secret = ?, mfa_totp_enabled = 1 WHERE id = ?");
        $stmt->execute([$secret, $user_id]);
        
        unset($_SESSION['mfa_setup_secret']);
        add_log('MFA_TOTP_ENABLED', 'Włączono weryfikację TOTP.', 'INFO', $user_id);
        
        $_SESSION['success_message'] = 'Aplikacja uwierzytelniająca (TOTP) została pomyślnie skonfigurowana i włączona.';
        redirect('/settings?tab=mfa');
        
    } else {
        // Błędny kod
        $_SESSION['error_message'] = 'Podany kod weryfikacyjny jest nieprawidłowy. Spróbuj ponownie.';
        redirect('/settings?tab=mfa');
    }

} catch (Exception $e) {
    error_log("Błąd weryfikacji TOTP: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd serwera podczas weryfikacji kodu. Spróbuj ponownie.';
    redirect('/settings?tab=mfa');
}
