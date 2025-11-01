<?php
// Plik: /index.php

// Na środowisku produkcyjnym błędy powinny być logowane, a nie wyświetlane.
// Te linie zostały usunięte, aby uniknąć ujawniania wrażliwych informacji.
// ini_set('display_errors', 0);
// error_reporting(0);


// 1. Inicjalizacja
require_once __DIR__ . '/config/boot.php';

// 2. Ładowanie sesji i uwierzytelniania
require_once __DIR__ . '/src/auth.php'; // Ładuje też functions.php

// 3. Routing
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = ''; 
$route_path = str_replace($base_path, '', $request_uri);
$route_path = parse_url($route_path, PHP_URL_PATH);
$route_path = trim($route_path, '/');

// POPRAWKA (WORKAROUND): Serwer home.pl dodaje prefiks 'auth/' do ścieżek.
// Ta linia go usuwa, aby dopasować trasy do oczekiwanych (np. 'login' zamiast 'auth/login').
if (str_starts_with($route_path, 'auth/')) {
    $route_path = substr($route_path, 5); // Usuń 'auth/' (5 znaków)
}

// Globalne zmienne dla widoków
global $pdo, $user;
$user = null;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Poprawiona mapa routingu
$routes = [
    'GET_VIEW' => [
        'dashboard' => 'dashboard.php',
        'login' => 'login.php',
        'settings' => 'settings.php',
        'mfa/verify' => 'mfa_verify.php', // Nowa trasa dla widoku weryfikacji MFA
        'mfa-verify' => 'mfa_verify.php', // Alias dla kompatybilności
        'settings/mfa/totp/setup' => 'mfa_totp_setup.php',
        'logs' => 'logs.php',
        'reset_password' => 'reset_password.php',
    ],
    'GET_HANDLER' => [
        'logout' => 'logout.php',
        'settings/email/confirm' => 'settings_email_confirm.php',
    ],
    'POST_HANDLER' => [
        'login' => 'login_handler.php',
        'mfa/verify' => 'mfa_verify_handler.php',
        'settings/profile' => 'settings_profile_handler.php',
        'settings/password' => 'settings_password_handler.php',
        'settings/email/request' => 'settings_email_request.php',
        'settings/email/cancel' => 'settings_email_cancel.php',
        'reset_password/request' => 'settings_password_request.php',
        'reset_password/set' => 'settings_password_reset.php',
        'settings/mfa/totp/verify' => 'mfa_totp_verify.php',
        'settings/mfa/totp/disable' => 'mfa_totp_disable.php',
        'settings/mfa/email/toggle' => 'mfa_email_toggle.php',
    ],
];

$method = $_SERVER['REQUEST_METHOD'];

// Logika dla 2FA (Brama weryfikacyjna)
if (
    $method === 'GET' &&
    isset($_SESSION['mfa_pending_user_id']) && // Użyj nowego, bezpieczniejszego klucza
    !in_array($route_path, ['mfa/verify', 'mfa-verify', 'logout']) // Dozwolone trasy
) {
    // Jeśli użytkownik jest w trakcie MFA i próbuje wejść gdzie indziej,
    // przekieruj go z powrotem do strony weryfikacji.
    redirect('/mfa/verify');
}

// Logika routingu
try {
    if ($method === 'GET') {
        if (isset($routes['GET_VIEW'][$route_path])) {
            $file = $routes['GET_VIEW'][$route_path];
            $view_path = __DIR__ . '/views/' . $file;
            if (file_exists($view_path)) { require_once $view_path; } 
            else { throw new Exception("404 - Not Found (View missing: {$file})"); }
        } elseif (isset($routes['GET_HANDLER'][$route_path])) {
            $file = $routes['GET_HANDLER'][$route_path];
            $handler_path = __DIR__ . '/src/handlers/' . $file;
            if (file_exists($handler_path)) { require_once $handler_path; } 
            else { throw new Exception("404 - Not Found (Handler missing: {$file})"); }
        } else {
            if ($route_path === '') {
                if (is_logged_in()) { redirect('/dashboard'); } 
                else { redirect('/login'); }
            } else {
                throw new Exception("404 - Not Found (Unknown GET path: {$route_path})");
            }
        }
    } elseif ($method === 'POST') {
        if (isset($routes['POST_HANDLER'][$route_path])) {
            $file = $routes['POST_HANDLER'][$route_path];
            $handler_path = __DIR__ . '/src/handlers/' . $file;
            if (file_exists($handler_path)) { require_once $handler_path; } 
            else { throw new Exception("404 - Not Found (Handler missing: {$file})"); }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
    } else {
        http_response_code(405);
        echo "405 Method Not Allowed";
    }
} catch (Exception $e) {
    http_response_code(404);
    echo $e->getMessage();
}