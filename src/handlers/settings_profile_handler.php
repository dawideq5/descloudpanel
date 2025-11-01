<?php
// Plik: /src/handlers/settings_profile_handler.php

if (!is_logged_in()) {
    redirect('/login');
}

global $pdo;
$user_id = $_SESSION['user_id'];
$old_username = $_SESSION['username'];

// Odczyt wszystkich pól z formularza
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$phone_private = trim($_POST['phone_private'] ?? '');
$phone_work = trim($_POST['phone_work'] ?? '');
$email_work = trim($_POST['email_work'] ?? '');
$new_email = trim($_POST['email'] ?? '');

try {
    // Pobranie istniejących danych
    $stmt = $pdo->prepare("SELECT email, `first name`, last_name, phone_private, phone_work, email_work, mfa_email_enabled FROM users WHERE id = ?");
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
    $set_null_if_empty = function($value) {
        return $value === '' ? null : $value;
    };
    
    // --- ZMIANY W POLACH PROFILU ---
    
    // 1. Pole Imię (first name) - Używamy nazwy kolumny `first name`
    // 🚨 POPRAWKA: Upewnienie się, że $user['first name'] jest traktowane jako puste, jeśli jest NULL
    // Logika naśladująca 'last_name':
    $user_first_name = trim($user['first name'] ?? ''); 
    if ($first_name !== $user_first_name) {
        $updates[] = "`first name` = ?"; // Użycie backticków dla nazwy kolumny ze spacją
        $params[] = $set_null_if_empty($first_name);
        $log_details[] = "Imię zmienione z '{$user_first_name}' na '{$first_name}'";
    }

    // 2. Nazwisko (last_name) - PRZYKŁAD, KTÓRY DZIAŁA
    $user_last_name = trim($user['last_name'] ?? '');
    if ($last_name !== $user_last_name) {
        $updates[] = "last_name = ?";
        $params[] = $set_null_if_empty($last_name);
        $log_details[] = "Nazwisko zmienione z '{$user_last_name}' na '{$last_name}'";
    }
    
    // ... (pozostały kod pól profilu jest pominięty, ponieważ jest poprawny)
    
    // 3. Telefon prywatny (phone_private)
    $user_phone_private = trim($user['phone_private'] ?? '');
    if ($phone_private !== $user_phone_private) {
        $updates[] = "phone_private = ?";
        $params[] = $set_null_if_empty($phone_private);
        $log_details[] = "Telefon prywatny zmieniony";
    }

    // 4. Telefon służbowy (phone_work)
    $user_phone_work = trim($user['phone_work'] ?? '');
    if ($phone_work !== $user_phone_work) {
        $updates[] = "phone_work = ?";
        $params[] = $set_null_if_empty($phone_work);
        $log_details[] = "Telefon służbowy zmieniony";
    }

    // 5. E-mail służbowy (email_work)
    $user_email_work = trim($user['email_work'] ?? '');
    if ($email_work !== $user_email_work) {
        $updates[] = "email_work = ?";
        $params[] = $set_null_if_empty($email_work);
        $log_details[] = "E-mail służbowy zmieniony";
    }
    
    // --- ZMIANA GŁÓWNEGO E-MAILA ---
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


    // --- WYKONANIE AKTUALIZACJI ---
    if (!empty($updates)) {
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $user_id;
        $stmt_update = $pdo->prepare($sql);
        
        if (!$stmt_update->execute($params)) {
              throw new Exception('Błąd zapisu danych do bazy.');
        }

        $log_message = "Zapisano zmiany w profilu: " . implode('; ', $log_details);
        add_log('SETTINGS_PROFILE_UPDATE', $log_message, 'INFO', $user_id);
        
        $success_msg = 'Zmiany profilu zostały zapisane.';
        
        // 🚨 Wprowadzam poprawki z poprzedniej rundy (dla wyświetlania)
        if (isset($_SESSION['user'])) {
              // Używamy klucza 'first_name', aby być spójnym z logiką poniżej i widokami
              $_SESSION['user']['first_name'] = $set_null_if_empty($first_name); 
              $_SESSION['user']['last_name'] = $set_null_if_empty($last_name);
              $_SESSION['user']['phone_private'] = $set_null_if_empty($phone_private);
              $_SESSION['user']['phone_work'] = $set_null_if_empty($phone_work);
              $_SESSION['user']['email_work'] = $set_null_if_empty($email_work);
              unset($_SESSION['user']['first name']); // Usunięcie klucza ze spacją, jeśli istniał
        }
        
        // Aktualizacja zmiennej sesyjnej dla email/login
        if ($new_email !== $old_email) {
              $success_msg = 'Adres e-mail został pomyślnie zmieniony.';
              if (isset($_SESSION['mfa_email_disabled'])) {
                 $success_msg .= ' Uwierzytelnianie e-mailem zostało wyłączone i wymaga ponownej konfiguracji.';
                 unset($_SESSION['mfa_email_disabled']);
              }
              $_SESSION['username'] = $new_email; 
        }

        // Przeładowanie pełnego obiektu użytkownika do sesji
        $stmt_reload = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_reload->execute([$user_id]);
        $reloaded_user = $stmt_reload->fetch(PDO::FETCH_ASSOC);

        if ($reloaded_user) {
            $reloaded_user['first_name'] = $reloaded_user['first name'] ?? null;
            unset($reloaded_user['password_hash']);
            unset($reloaded_user['first name']); // Usunięcie klucza ze spacją
            $_SESSION['user'] = $reloaded_user;
        }


        $_SESSION['success_message'] = $success_msg; 

    } else {
        // Ten blok jest teraz osiągalny tylko wtedy, gdy faktycznie nie ma żadnych zmian
        $_SESSION['error_message'] = 'Nie wprowadzono żadnych zmian.';
    }

    redirect('/settings?tab=profile');

} catch (Exception $e) {
    error_log("Błąd zapisu profilu: " . $e->getMessage());
    $_SESSION['error_message'] = 'Wystąpił błąd serwera: ' . $e->getMessage();
    redirect('/settings?tab=profile');
}