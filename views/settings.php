<?php
// Plik: /views/settings.php
$page_title = 'Ustawienia konta';
$page_path = 'settings';
require_once __DIR__ . '/partials/header.php';

$active_tab = $_GET['tab'] ?? 'profile';

// Pobranie danych użytkownika do wyświetlenia w widokach
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('/logout');
}

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Ustawienia konta</h1>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="list-group">
            <a href="/settings?tab=profile" class="list-group-item list-group-item-action <?php echo ($active_tab === 'profile') ? 'active' : ''; ?>">
                <i class="bi bi-person-circle me-2"></i> Profil
            </a>
            <a href="/settings?tab=password" class="list-group-item list-group-item-action <?php echo ($active_tab === 'password') ? 'active' : ''; ?>">
                <i class="bi bi-lock-fill me-2"></i> Hasło
            </a>
            <a href="/settings?tab=mfa" class="list-group-item list-group-item-action <?php echo ($active_tab === 'mfa') ? 'active' : ''; ?>">
                <i class="bi bi-shield-lock me-2"></i> Weryfikacja dwuetapowa
            </a>
            </div>
    </div>
    <div class="col-md-9">
        <?php if ($active_tab === 'profile'): ?>
            <?php require __DIR__ . '/partials/settings/_profile.php'; ?>
        <?php elseif ($active_tab === 'password'): ?>
            <?php require __DIR__ . '/partials/settings/_password.php'; ?>
        <?php elseif ($active_tab === 'mfa'): ?>
            <?php require __DIR__ . '/partials/settings/_mfa.php'; ?>
        <?php elseif ($active_tab === 'email'): ?>
            <div class="alert alert-warning">Ta zakładka została przeniesiona do sekcji Profil.</div>
            <?php require __DIR__ . '/partials/settings/_email.php'; ?> 
        <?php else: ?>
            <?php require __DIR__ . '/partials/settings/_profile.php'; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/partials/footer.php';
?>