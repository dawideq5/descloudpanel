<?php
// Plik: /views/partials/settings/_profile.php
global $user;

// Funkcja pomocnicza dla bezpiecznego pobierania Imienia
// Preferuje 'first_name', ale sprawdza też 'first name' (klucz ze spacją)
$first_name_value = $user['first_name'] ?? $user['first name'] ?? '';
?>
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Informacje o profilu</h5>
    </div>
    <div class="card-body">
        <form action="/settings/profile" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Nazwa użytkownika (login)</label>
                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly disabled>
                <small class="text-muted">Nazwy użytkownika nie można zmienić.</small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">Imię</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name_value); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Nazwisko</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Główny e-mail (do logowania)</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="phone_private" class="form-label">Telefon prywatny</label>
                    <input type="tel" class="form-control" id="phone_private" name="phone_private" value="<?php echo htmlspecialchars($user['phone_private'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone_work" class="form-label">Telefon służbowy</label>
                    <input type="tel" class="form-control" id="phone_work" name="phone_work" value="<?php echo htmlspecialchars($user['phone_work'] ?? ''); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label for="email_work" class="form-label">Służbowy e-mail</label>
                <input type="email" class="form-control" id="email_work" name="email_work" value="<?php echo htmlspecialchars($user['email_work'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Zapisz zmiany profilu</button>
        </form>
    </div>
</div>