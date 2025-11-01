<?php
// Plik: /views/partials/header.php
// Wersja 4 - Aktywny link Ustawienia
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Panel Descloud'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: 260px;
            background-color: #343a40;
            padding: 1rem;
        }
        .main-content { margin-left: 260px; padding: 1.5rem; }
        .sidebar .nav-link { color: #ccc; }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; font-weight: bold; }
    </style>
</head>
<body>
<?php if (is_logged_in()): ?>
    <div class="sidebar d-flex flex-column p-3 text-white bg-dark">
        <a href="/dashboard" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-cloud-fill me-2" style="font-size: 1.5rem;"></i>
            <span class="fs-4">Descloud Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            
            <?php if (can('module_dashboard')): ?>
            <li class="nav-item">
                <a href="/dashboard" class="nav-link <?php echo ($page_path === 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Panel Główny
                </a>
            </li>
            <?php endif; ?>

            <?php if (can('module_warranty')): ?>
            <li class="nav-item">
                <a href="/warranty" class="nav-link <?php echo ($page_path === 'warranty') ? 'active' : ''; ?>">
                    <i class="bi bi-shield-check me-2"></i> Gwarancje
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (can('module_team')): ?>
            <li class="nav-item">
                <a href="/team" class="nav-link <?php echo ($page_path === 'team') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill me-2"></i> Zespół
                </a>
            </li>
            <?php endif; ?>

            <?php if (can('module_logs')): ?>
            <li class="nav-item">
                <a href="/logs" class="nav-link <?php echo ($page_path === 'logs') ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-data me-2"></i> Logi Systemowe
                </a>
            </li>
            <?php endif; ?>
            </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>
                <strong><?php echo get_display_name(); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="/settings">Ustawienia</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/logout">Wyloguj</a></li>
            </ul>
        </div>
    </div>
    
    <main class="main-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

<?php else: ?>
    <main>
<?php endif; ?>