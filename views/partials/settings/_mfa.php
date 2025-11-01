<?php
// Plik: /views/partials/settings/_mfa.php
global $user;

// Sprawdź, czy mamy pokazać pole na kod 2FA e-mail
$show_mfa_email_code_input = $_SESSION['show_mfa_email_code_input'] ?? false;
unset($_SESSION['show_mfa_email_code_input']); // Wyczyść flagę
?>
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-phone-fill me-2"></i>
                <h5 class="mb-0">Aplikacja uwierzytelniająca (TOTP)</h5>
            </div>
            <div class="card-body">
                <?php if ($user['mfa_totp_enabled']): ?>
                    <div class="alert alert-success">Ta metoda jest <strong>AKTYWNA</strong>.</div>
                    <p>Używasz aplikacji takiej jak Google Authenticator lub Authy.</p>

                    <button type="button" id="show-disable-totp-btn" class="btn btn-danger">Wyłącz aplikację uwierzytelniającą</button>

                    <form action="/settings/mfa/totp/disable" method="POST" id="disable-totp-form" class="mt-3" style="display: none;">
                        <div class="mb-3">
                            <label for="disable_totp_pass" class="form-label">Potwierdź hasłem, aby kontynuować</label>
                            <input type="password" class="form-control" id="disable_totp_pass" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-danger">Potwierdź wyłączenie</button>
                        <button type="button" id="cancel-disable-totp-btn" class="btn btn-secondary">Anuluj</button>
                    </form>

                <?php else: ?>
                    <div class="alert alert-secondary">Ta metoda jest <strong>NIEAKTYWNA</strong>.</div>
                    <p>Użyj aplikacji takiej jak Google Authenticator, aby skanować kody QR i generować kody jednorazowe.</p>
                    <a href="/settings/mfa/totp/setup" class="btn btn-primary">Skonfiguruj aplikację</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4" id="mfa-email-card">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-envelope-fill me-2"></i>
                <h5 class="mb-0">Weryfikacja przez e-mail</h5>
            </div>
            <div class="card-body">
                <p>Otrzymasz kod jednorazowy na swój adres e-mail (<?php echo htmlspecialchars($user['email']); ?>) podczas logowania.</p>

                <form action="/settings/mfa/email/toggle" method="POST">
                    <?php if ($user['mfa_email_enabled']): ?>
                        <input type="hidden" name="action_type" value="disable">
                        <div class="alert alert-success">Ta metoda jest <strong>AKTYWNA</strong>.</div>
                        <div class="mb-3">
                            <label for="disable_email_pass" class="form-label">Potwierdź hasłem</label>
                            <input type="password" class="form-control" id="disable_email_pass" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-danger">Wyłącz weryfikację e-mail</button>

                    <?php else: ?>
                        <input type="hidden" name="action_type" value="enable">
                        <div class="alert alert-secondary">Ta metoda jest <strong>NIEAKTYWNA</strong>.</div>

                        <div id="mfa-email-code-group" class="mb-3" style="<?php echo $show_mfa_email_code_input ? '' : 'display: none;'; ?>">
                            <label for="mfa_code" class="form-label">Kod weryfikacyjny</label>
                            <input type="text" class="form-control" id="mfa_code" name="mfa_code" placeholder="Wpisz kod z e-maila">
                        </div>

                        <button type="submit" id="mfa-email-submit-btn" class="btn btn-primary">
                            <?php echo $show_mfa_email_code_input ? 'Potwierdź kod' : 'Wyślij kod, aby włączyć'; ?>
                        </button>

                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const showBtn = document.getElementById('show-disable-totp-btn');
    const cancelBtn = document.getElementById('cancel-disable-totp-btn');
    const disableForm = document.getElementById('disable-totp-form');
    const totpPassInput = document.getElementById('disable_totp_pass');

    if (showBtn && cancelBtn && disableForm) {
        showBtn.addEventListener('click', function() {
            disableForm.style.display = 'block';
            showBtn.style.display = 'none';
            totpPassInput.focus();
        });

        cancelBtn.addEventListener('click', function() {
            disableForm.style.display = 'none';
            showBtn.style.display = 'block';
            totpPassInput.value = '';
        });
    }

    const emailCodeInput = document.getElementById('mfa-email-code-group');
    const emailSubmitBtn = document.getElementById('mfa-email-submit-btn');

    <?php if ($show_mfa_email_code_input && $emailCodeInput): ?>
        emailCodeInput.style.display = 'block';
        emailSubmitBtn.textContent = 'Potwierdź kod';
    <?php endif; ?>

});
</script>