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

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 'profile') ? 'active' : ''; ?>" href="/settings?tab=profile">
                    <i class="bi bi-person-circle me-1"></i> Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 'avatar') ? 'active' : ''; ?>" href="/settings?tab=avatar">
                    <i class="bi bi-image me-1"></i> Zdjęcie profilowe
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 'password') ? 'active' : ''; ?>" href="/settings?tab=password">
                    <i class="bi bi-lock-fill me-1"></i> Hasło
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($active_tab === 'mfa') ? 'active' : ''; ?>" href="/settings?tab=mfa">
                    <i class="bi bi-shield-lock me-1"></i> Weryfikacja dwuetapowa
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <?php
        switch ($active_tab) {
            case 'profile':
                require __DIR__ . '/partials/settings/_profile.php';
                break;
            case 'avatar':
                require __DIR__ . '/partials/settings/_avatar.php';
                break;
            case 'password':
                require __DIR__ . '/partials/settings/_password.php';
                break;
            case 'mfa':
                require __DIR__ . '/partials/settings/_mfa.php';
                break;
            default:
                require __DIR__ . '/partials/settings/_profile.php';
                break;
        }
        ?>
    </div>
</div>

<?php
require_once __DIR__ . '/partials/footer.php';
?>