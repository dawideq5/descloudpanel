<?php
// Plik: /views/reset_password.php
global $pdo;
require_once __DIR__ . '/../config/boot.php'; // Tylko baza i vendor

$token = $_GET['token'] ?? null;
$error = null;
$valid_token = false;

if ($token) {
    try {
        // Sprawdzamy token w tabeli users, zgodnie z Twoim schematem
        $stmt = $pdo->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
        $stmt->execute([$token]);
        if ($stmt->fetch()) {
            $valid_token = true;
        } else {
            $error = "Token jest nieprawidłowy lub wygasł.";
        }
    } catch (PDOException $e) {
        $error = "Błąd bazy danych.";
    }
} else {
    $error = "Brak tokenu.";
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Reset Hasła</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; }
        .form-signin { width: 100%; max-width: 400px; padding: 2rem; background-color: #fff; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <h1 class="h3 mb-3 fw-normal">Ustaw nowe hasło</h1>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                if ($_GET['error'] == 'mismatch') echo 'Hasła nie są zgodne.';
                if ($_GET['error'] == 'short') echo 'Hasło musi mieć co najmniej 8 znaków.';
                ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($valid_token): ?>
            <form action="/handle-reset-password" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Nowe hasło" required>
                    <label for="password">Nowe hasło (min. 8 znaków)</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Potwierdź hasło" required>
                    <label for="password_confirm">Potwierdź hasło</label>
                </div>
                <button class="w-100 btn btn-lg btn-primary" type="submit">Ustaw hasło</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>