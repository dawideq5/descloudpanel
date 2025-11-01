<?php
// Plik: /views/login.php
$page_title = 'Zaloguj się';
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
    </style>
</head>
<body class="text-center">
    <main class="form-signin">
        <form action="/auth/login" method="POST">
            <i class="bi bi-cloud-fill" style="font-size: 3rem; color: #0d6efd;"></i>
            <h1 class="h3 mb-3 fw-normal">Panel Descloud</h1>

            <?php // Wyświetlanie błędów
            if (isset($_GET['error'])):
                $errorMsg = 'Nieznany błąd.';
                if ($_GET['error'] == '1') $errorMsg = 'Nieprawidłowy login lub hasło.';
                if ($_GET['error'] == 'db') $errorMsg = 'Błąd serwera. Spróbuj później.';
                if ($_GET['error'] == 'locked') $errorMsg = 'Zbyt wiele prób. Spróbuj ponownie za 5 minut.';
                if ($_GET['error'] == 'mfa_failed') $errorMsg = 'Weryfikacja dwuetapowa nie powiodła się.';
                if ($_GET['error'] == 'mfa_error') $errorMsg = 'Wystąpił błąd w trakcie weryfikacji dwuetapowej. Spróbuj ponownie.';
                if ($_GET['error'] == 'session_expired') $errorMsg = 'Sesja wygasła lub została przerwana. Zaloguj się ponownie.'; // POPRAWKA
            ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>
            
            <?php // Komunikaty o sukcesie
            if (isset($_GET['logged_out'])): ?>
                <div class="alert alert-success" role="alert">
                    Pomyślnie wylogowano.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 'password_reset'): ?>
                <div class="alert alert-success" role="alert">
                    Hasło zostało pomyślnie zmienione. Możesz się teraz zalogować.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['success']) && $_GET['success'] == 'email_changed'): ?>
                <div class="alert alert-success" role="alert">
                    Adres e-mail został zmieniony. Zaloguj się ponownie.
                </div>
            <?php endif; ?>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Login" required autofocus>
                <label for="username">Login</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Hasło" required>
                <label for="password">Hasło</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">Zaloguj się</button>
            <p class="mt-5 mb-3 text-muted">&copy; Descloud <?php echo date('Y'); ?></p>
        </form>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>