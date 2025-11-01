<?php
// Plik: /views/register.php
$page_title = 'Rejestracja';
require_once __DIR__ . '/partials/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-body">
                    <h3 class="card-title text-center">Rejestracja</h3>
                    <form action="/src/handlers/register_handler.php" method="post">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Imię</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nazwisko</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adres e-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Hasło</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Potwierdź hasło</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Zarejestruj</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/partials/footer.php';
?>
