<?php
// Plik: /views/partials/settings/_password.php
?>
<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Zmiana hasła</h5>
    </div>
    <div class="card-body">
        <form action="/settings/password" method="POST">
            <div class="mb-3">
                <label for="password_current" class="form-label">Aktualne hasło</label>
                <input type="password" class="form-control" id="password_current" name="password_current" required>
            </div>
            <div class="mb-3">
                <label for="password_new" class="form-label">Nowe hasło</label>
                <input type="password" class="form-control" id="password_new" name="password_new" required minlength="10">
            </div>
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Potwierdź nowe hasło</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="btn btn-primary">Zmień hasło</button>
        </form>
    </div>
</div>