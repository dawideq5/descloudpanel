<?php
// Plik: /views/logs.php
$page_title = 'Logi Systemowe';
require_once __DIR__ . '/partials/header.php'; // Ładuje header z sidebarem

// Pobierz logi z bazy
global $pdo;
$logs = [];
try {
    // Pobierz ostatnie 100 logów, łącząc z tabelą 'users'
    // Używamy LEFT JOIN na wypadek, gdyby użytkownik został usunięty
    $stmt = $pdo->query("
        SELECT 
            sl.*, 
            u_by.username AS performed_by_username
        FROM system_logs sl
        LEFT JOIN users u_by ON sl.performed_by_user_id = u_by.id
        ORDER BY sl.log_timestamp DESC
        LIMIT 100
    ");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    // Wyświetl błąd, jeśli pobieranie logów się nie uda
    echo "<div class='alert alert-danger'>Błąd podczas ładowania logów: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Logi Systemowe</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Ostatnie 100 zdarzeń</h5>
            
            <div class="table-responsive mt-3">
                <table class="table table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Data</th>
                            <th scope="col">Poziom</th>
                            <th scope="col">Wykonał</th>
                            <th scope="col">Typ Zdarzenia</th>
                            <th scope="col">Opis</th>
                            <th scope="col">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center p-3">Brak logów w systemie.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['log_timestamp']); ?></td>
                                    <td>
                                        <?php 
                                        $level = htmlspecialchars($log['log_level']);
                                        $badge_class = 'bg-secondary';
                                        if ($level === 'INFO') $badge_class = 'bg-primary';
                                        if ($level === 'WARNING') $badge_class = 'bg-warning text-dark';
                                        if ($level === 'ERROR' || $level === 'CRITICAL') $badge_class = 'bg-danger';
                                        echo "<span class='badge $badge_class'>$level</span>";
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['performed_by_username'] ?? 'System'); ?></td>
                                    <td><code><?php echo htmlspecialchars($log['event_type']); ?></code></td>
                                    <td><?php echo htmlspecialchars($log['message']); ?></td>
                                    <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/partials/footer.php'; // Ładuje stopkę
?>