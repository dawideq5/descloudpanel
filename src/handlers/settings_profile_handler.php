<?php
// Plik: /src/handlers/settings_profile_handler.php
// Wersja 2 - Dostosowano do nowego schematu bazy danych

require_once __DIR__ . '/../auth.php';
require_login();

global $pdo;
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/settings?tab=profile');
}

// Odczyt pól z formularza (zgodnie z nowym schematem)
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$new_email = trim($_POST['email'] ?? '');

try {
    // Pobranie istniejących danych użytkownika
    $stmt = $pdo->prepare("SELECT email, first_name, last_name, phone_number, mfa_email_enabled FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Nie znaleziono użytkownika.');
    }

    $old_email = $user['email'];
    $updates = [];
    $params = [];
    $log_details = [];
    
    // Funkcja pomocnicza: Zwraca NULL jeśli pole jest puste, inaczej wartość
    $set_null_if_empty = fn($value) => $value === '' ? null : $value;
    
    // --- Porównanie i przygotowanie zmian ---

    if ($first_name !== ($user['first_name'] ?? '')) {
        $updates[] = "first_name = ?";
        $params[] = $first_name;
        $log_details[] = "Imię zmienione z '{$user['first_name']}' na '{$first_name}'";
    }

    if ($last_name !== ($user['last_name'] ?? '')) {
        $updates[] = "last_name = ?";
        $params[] = $last_name;
        $log_details[] = "Nazwisko zmienione z '{$user['last_name']}' na '{$last_name}'";
    }
    
    if ($phone_number !== ($user['phone_number'] ?? '')) {
        $updates[] = "phone_number = ?";
        $params[] = $set_null_if_empty($phone_number);
        $log_details[] = "Numer telefonu zmieniony";
    }
    
    // --- Zmiana głównego adresu e-mail ---
    if ($new_email !== $old_email) {
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = 'Nowy adres e-mail jest nieprawidłowy.';
            redirect('/settings?tab=profile');
        }
        
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->execute([$new_email, $user_id]);
        if ($stmt_check->fetch()) {
            $_SESSION['error_message'] = 'Ten adres e-mail jest już używany przez inne konto.';
            redirect('/settings?tab=profile');
        }
        
        $updates[] = "email = ?";
        $params[] = $new_email;
        $log_details[] = "Główny e-mail zmieniony z '{$old_email}' na '{$new_email}'";
        
        if ($user['mfa_email_enabled']) {
            $updates[] = "mfa_email_enabled = 0";
            $log_details[] = "MFA E-mail zostało automatycznie wyłączone z powodu zmiany adresu.";
            $_SESSION['mfa_email_disabled'] = true; 
        }
    }

    // --- Wykonanie aktualizacji ---
    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $user_id;
        
        $stmt_update = $pdo->prepare($sql);
        $stmt_update->execute($params);

        $log_message = "Zapisano zmiany w profilu: " . implode('; ', $log_details);
        add_log('SETTINGS_PROFILE_UPDATE', $log_message, 'INFO', $user_id);
        
        // Aktualizacja danych w sesji, aby były od razu widoczne
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        if ($new_email !== $old_email) {
            $_SESSION['username'] = $new_email; // 'username' w sesji trzyma główny identyfikator
        }

        $success_msg = 'Zmiany profilu zostały zapisane.';
        if (isset($_SESSION['mfa_email_disabled'])) {
            $success_msg .= ' Uwierzytelnianie e-mailem zostało wyłączone i wymaga ponownej konfiguracji.';
            unset($_SESSION['mfa_email_disabled']);
        }
        $_SESSION['success_message'] = $success_msg; 
    } else {
        $_SESSION['error_message'] = 'Nie wprowadzono żadnych zmian.';
    }

    redirect('/settings?tab=profile');

} catch (Exception $e) {
    error_log("Błąd zapisu profilu: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd serwera: ' . $e->getMessage();
    redirect('/settings?tab=profile');
}
