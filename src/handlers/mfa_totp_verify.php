<?php
// Plik: /src/handlers/mfa_totp_verify.php
require_once __DIR__ . '/../auth.php'; 
use RobThree\Auth\TwoFactorAuth;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_logged_in()) {
    header('Location: /login');
    exit;
}

global $pdo;
$user_id = $_SESSION['user_id'];
$code = $_POST['totp_code'] ?? '';
$secret = $_SESSION['mfa_setup_secret'] ?? null;

if (!$secret) {
    header('Location: /settings?error=mfa_verify_failed');
    exit;
}

try {
    $tfa = new TwoFactorAuth('Descloud');
    
    if ($tfa->verifyCode($secret, $code)) {
        // Sukces! Zapisz sekret w bazie
        // TODO: W środowisku produkcyjnym ten sekret powinien być zaszyfrowany!
        // Na razie zapisujemy go jawnie.
        
        $stmt = $pdo->prepare("UPDATE users SET mfa_totp_secret = ?, mfa_totp_enabled = 1 WHERE id = ?");
        $stmt->execute([$secret, $user_id]);
        
        unset($_SESSION['mfa_setup_secret']);
        add_log('MFA_TOTP_ENABLED', 'Włączono weryfikację TOTP.', 'INFO', $user_id);
        
        header('Location: /settings?success=mfa_totp_enabled');
        exit;
        
    } else {
        // Błędny kod
        header('Location: /settings?error=mfa_verify_failed');
        exit;
    }

} catch (Exception $e) {
    error_log("Błąd weryfikacji TOTP: " . $e->getMessage());
    header('Location: /settings?error=db');
    exit;
}