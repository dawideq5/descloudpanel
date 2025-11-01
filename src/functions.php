<?php
// Plik: /src/functions.php
// Centralne funkcje pomocnicze

/**
 * Rejestruje zdarzenie w logu systemowym.
 * Działa tylko, jeśli $pdo jest dostępne globalnie.
 *
 * @param string $event_type Typ zdarzenia (np. 'AUTH_LOGIN_SUCCESS')
 * @param string $message Opis zdarzenia
 * @param string $log_level POZIOM (INFO, WARNING, ERROR, CRITICAL)
 * @param int|null $user_id ID użytkownika, którego dotyczy zdarzenie (opcjonalne)
 */
function add_log(string $event_type, string $message, string $log_level = 'INFO', ?int $user_id = null) {
    global $pdo; // Użyj globalnego połączenia $pdo

    // Jeśli funkcja jest wywoływana, gdy $pdo nie istnieje (np. błąd bazy), nie rób nic
    if (!isset($pdo) || !$pdo instanceof PDO) {
        error_log("Błąd logowania (add_log): PDO nie jest dostępne.");
        return;
    }

    // Kto wykonał akcję? (Pobierz z sesji)
    $performed_by_user_id = $_SESSION['user_id'] ?? null;
    
    // Jeśli $user_id nie jest podane, a ktoś jest zalogowany,
    // załóżmy, że akcja dotyczy zalogowanego użytkownika.
    if ($user_id === null && $performed_by_user_id !== null) {
        $user_id = $performed_by_user_id;
    }
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    
    try {
        // === POPRAWKA LOGÓW: Dodano kolumnę `timestamp` z funkcją NOW() dla większej stabilności bazy danych ===
        $stmt = $pdo->prepare(
            "INSERT INTO system_logs (`timestamp`, `user id`, performed_by_user_id, ip_address, event_type, log_level, message) 
             VALUES (NOW(), ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $performed_by_user_id, $ip_address, $event_type, $log_level, $message]);
        
    } catch (PDOException $e) {
        // Jeśli logowanie zawiedzie, zapisz błąd do głównego logu serwera
        error_log("Krytyczny błąd podczas zapisu do system_logs: " . $e->getMessage());
    }
}


// === NOWA FUNKCJA: Ładowanie uprawnień (brakowało jej) ===

/**
 * Ładuje uprawnienia użytkownika do sesji.
 * Funkcja wewnętrzna, używana przez can().
 */
function _load_user_permissions() {
    global $pdo;
    
    // Sprawdź, czy użytkownik jest zalogowany i czy uprawnienia nie są już załadowane
    if (!isset($_SESSION['user_id']) || isset($_SESSION['permissions'])) {
        return;
    }

    try {
        // Pobierz user_type_id dla zalogowanego użytkownika
        $stmt_user_type = $pdo->prepare("SELECT user_type_id FROM users WHERE id = ?");
        $stmt_user_type->execute([$_SESSION['user_id']]);
        $user_type_id = $stmt_user_type->fetchColumn();

        if (!$user_type_id) {
            $_SESSION['permissions'] = []; // Brak typu użytkownika, brak uprawnień
            return;
        }

        // Pobierz wszystkie klucze uprawnień (permission_key) dla tego typu użytkownika
        $stmt_perms = $pdo->prepare("
            SELECT p.permission_key
            FROM permissions p
            JOIN user_type_permissions utp ON p.id = utp.permission_id
            WHERE utp.user_type_id = ?
        ");
        $stmt_perms->execute([$user_type_id]);
        
        $permissions = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);
        
        // Zapisz uprawnienia w sesji jako tablicę (dla szybkiego dostępu)
        $_SESSION['permissions'] = array_flip($permissions);

    } catch (PDOException $e) {
        error_log("Błąd ładowania uprawnień użytkownika: " . $e->getMessage());
        $_SESSION['permissions'] = []; // W razie błędu ustaw puste
    }
}

// === NOWA FUNKCJA: Sprawdzanie uprawnień (brakowało jej) ===

/**
 * Sprawdza, czy zalogowany użytkownik ma określone uprawnienie.
 *
 * @param string $permission_key Klucz uprawnienia (np. 'view_logs')
 * @return bool
 */
function can(string $permission_key): bool {
    // Jeśli uprawnienia nie są jeszcze w sesji, załaduj je
    if (!isset($_SESSION['permissions'])) {
        _load_user_permissions();
    }
    
    // Sprawdź, czy klucz uprawnienia istnieje w tablicy w sesji
    // Używamy isset() dla wydajności (szybsze niż in_array())
    return isset($_SESSION['permissions'][$permission_key]);
}

/**
 * Wysyła e-mail za pomocą skonfigurowanego mailera.
 *
 * @param string $to Adres odbiorcy
 * @param string $subject Temat wiadomości
 * @param string $body Treść wiadomości (HTML)
 * @throws \PHPMailer\PHPMailer\Exception Jeśli wysyłka zawiedzie
 * @return bool True jeśli wysłano
 */
function send_email(string $to, string $subject, string $body): bool {
    // === POPRAWKA MAILER: Usunięto blok try-catch, aby wyjątek PHPMailera był widoczny w handlerze ===
    $mailer = getMailer();
    $mailer->addAddress($to);
    $mailer->Subject = $subject;
    $mailer->Body = $body;

    // Mailer rzuci wyjątek, jeśli wystąpi błąd
    $mailer->send();
    return true; // Jeśli kod doszedł do tego miejsca, wysłano pomyślnie.
}