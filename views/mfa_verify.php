<?php
// Plik: /views/mfa_verify.php
// Wersja: Samowystarczalny widok weryfikacji 2FA
$page_title = 'Weryfikacja dwuetapowa';

// Jeśli nie jest załadowany przez router/autoloader, upewnij się, że mamy podstawowe funkcje
if (!function_exists('redirect')) {
    require_once __DIR__ . '/../src/auth.php'; // Załaduje require_once __DIR__ . '/../config/boot.php';
}

// === Sprawdzenie sesji ===
if (!isset($_SESSION['mfa_pending_user_id'])) {
    session_destroy();
    redirect('/login?error=session_expired');
}

$user_id = $_SESSION['mfa_pending_user_id'];
// Ta zmienna jest teraz poprawnie ustawiana w login_handler.php
$mfa_methods = $_SESSION['mfa_methods'] ?? [];

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Używamy styli z login.php do centrowania */
        html, body { height: 100%; }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .mfa-method-card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1.25rem;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <i class="bi bi-shield-lock-fill" style="font-size: 3rem; color: #0d6efd;"></i>
        <h3 class="card-title text-center mb-4">Weryfikacja dwuetapowa</h3>
        <p class="text-center text-muted">Twoje konto jest chronione. Wybierz metodę weryfikacji.</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        <form action="/mfa/verify" method="POST" id="mfa-verify-form">
            
            <?php if (in_array('totp', $mfa_methods)): ?>
            <div class="mfa-method-card mb-3" id="mfa-totp">
                <h5><i class="bi bi-phone-fill me-2"></i> Aplikacja uwierzytelniająca</h5>
                <p class="small text-muted">Wpisz 6-cyfrowy kod ze swojej aplikacji (np. Google Authenticator).</p>
                <div class="mb-3">
                    <label for="totp_code" class="form-label">Kod weryfikacyjny</label>
                    <input type="text" class="form-control" id="totp_code" name="totp_code" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" maxlength="6">
                </div>
                <input type="hidden" name="method" value="totp">
                <button type="submit" class="w-100 btn btn-lg btn-primary">Zweryfikuj kodem</button>
            </div>
            <?php endif; ?>

            <?php if (in_array('email', $mfa_methods)): ?>
            <div class="mfa-method-card <?php echo in_array('totp', $mfa_methods) ? 'mt-4' : 'mb-3'; ?>" id="mfa-email">
                <h5><i class="bi bi-envelope-fill me-2"></i> Kod e-mail</h5>
                
                <?php if (isset($_SESSION['mfa_email_sent'])): ?>
                    <div class="alert alert-info small p-2">Kod został wysłany na Twój adres e-mail.</div>
                    <div class="mb-3">
                        <label for="email_code" class="form-label">Kod weryfikacyjny</label>
                        <input type="text" class="form-control" id="email_code" name="email_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6">
                    </div>
                    <input type="hidden" name="method" value="email_code">
                    <button type="submit" class="w-100 btn btn-lg btn-primary">Zweryfikuj kodem</button>
                <?php else: ?>
                    <p class="small text-muted">Wyślemy 6-cyfrowy kod na Twój adres e-mail.</p>
                    <input type="hidden" name="method" value="email_send">
                    <button type="submit" class="w-100 btn btn-lg btn-secondary">Wyślij kod e-mail</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (in_array('webauthn', $mfa_methods)): ?>
            <div class="mfa-method-card mt-4" id="mfa-webauthn">
                <h5><i class="bi bi-key-fill me-2"></i> Klucz sprzętowy (WebAuthn)</h5>
                <p class="small text-muted">Użyj swojego klucza bezpieczeństwa.</p>
                <button type="button" class="w-100 btn btn-lg btn-secondary disabled">Weryfikuj kluczem</button>
            </div>
            <?php endif; ?>
            
        </form>
        
        <hr>
        <div class="text-center">
            <a href="/logout" class="text-muted small">Anuluj i wyloguj się</a>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>