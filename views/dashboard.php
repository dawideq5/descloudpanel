<?php
// Plik: /views/dashboard.php
$page_title = 'Panel Główny';
require_once __DIR__ . '/partials/header.php'; // Ładuje header z sidebarem
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Panel Główny</h1>
    </div>

    <div class="row g-4">
        
        <?php if (can('module_dashboard')): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-speedometer2" style="font-size: 3rem; color: #0d6efd;"></i>
                    <h5 class="card-title mt-3">Dashboard</h5>
                    <p class="card-text">Statystyki i przegląd systemu.</p>
                    <a href="/dashboard" class="btn btn-primary">Przejdź</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can('module_warranty')): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-shield-check" style="font-size: 3rem; color: #198754;"></i>
                    <h5 class="card-title mt-3">Gwarancje</h5>
                    <p class="card-text">Zarządzaj gwarancjami na szkło.</p>
                    <a href="/warranty" class="btn btn-primary">Przejdź</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (can('module_team')): ?>
         <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill" style="font-size: 3rem; color: #fd7e14;"></i>
                    <h5 class="card-title mt-3">Zespół</h5>
                    <p class="card-text">Zarządzaj użytkownikami i uprawnieniami.</p>
                    <a href="/team" class="btn btn-primary">Przejdź</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    
    <div class="alert alert-warning mt-4">
        <strong>Informacja:</strong> Widzisz tylko te moduły, do których masz uprawnienia.
    </div>

</div>

<?php
require_once __DIR__ . '/partials/footer.php'; // Ładuje stopkę
?>