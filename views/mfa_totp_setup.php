<?php
// Plik: /views/mfa_totp_setup.php
$page_title = 'Konfiguruj Aplikację Authenticator';
require_once __DIR__ . '/partials/header.php';
global $pdo;

use RobThree\Auth\TwoFactorAuth;

try {
    // Pobierz email użytkownika dla etykiety QR
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $email = $stmt->fetchColumn();

    $tfa = new TwoFactorAuth('Descloud');
    $secret = $tfa->createSecret();
    
    // Zapisz sekret tymczasowo w sesji
    $_SESSION['mfa_setup_secret'] = $secret;
    
    $qrCodeUrl = $tfa->getQRCodeImageAsDataUri('Descloud (' . $email . ')', $secret);

} catch (Exception $e) {
    die("Błąd generowania kodu: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Konfiguruj Aplikację (TOTP)</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title text-center">Krok 1: Zeskanuj kod QR</h5>
                    <p class="text-center">Użyj aplikacji uwierzytelniającej (np. Google Authenticator, Authy, Microsoft Authenticator), aby zeskanować ten kod.</p>
                    
                    <div class="text-center my-4">
                        <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="img-fluid border rounded">
                    </div>
                    
                    <p class="text-center text-muted small">Nie możesz zeskanować? Wprowadź ten kod ręcznie:<br>
                        <code><?php echo htmlspecialchars($secret); ?></code>
                    </p>
                    
                    <hr>
                    
                    <h5 class="card-title text-center mt-4">Krok 2: Weryfikuj kod</h5>
                    <p class="text-center">Wprowadź 6-cyfrowy kod wygenerowany przez aplikację, aby potwierdzić konfigurację.</p>
                    
                    <form action="/settings/mfa/totp/verify" method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="totp_code" name="totp_code" placeholder="123456" inputmode="numeric" pattern="[0-9]{6}" required>
                            <label for="totp_code">Kod 6-cyfrowy</label>
                        </div>
                        <button type="submit" class="w-100 btn btn-lg btn-primary">Włącz weryfikację</button>
                    </form>
                    
                    <a href="/settings" class="btn btn-link mt-3">Anuluj</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/partials/footer.php';
?>