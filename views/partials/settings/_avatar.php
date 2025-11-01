<?php
// Plik: /views/partials/settings/_avatar.php
?>
<h4 class="mb-3">Zdjęcie profilowe</h4>
<div class="row">
    <div class="col-md-4">
        <p class="text-muted">Aktualne zdjęcie</p>
        <img src="<?php echo htmlspecialchars($user['avatar_url'] ?? '/assets/images/default-avatar.png'); ?>" alt="Avatar" class="img-thumbnail" width="150">
    </div>
    <div class="col-md-8">
        <form action="/src/handlers/avatar_upload_handler.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="avatar" class="form-label">Wybierz nowe zdjęcie (maks. 2MB)</label>
                <input class="form-control" type="file" id="avatar" name="avatar" accept="image/png, image/jpeg, image/gif" required>
            </div>
            <button type="submit" class="btn btn-primary">Zmień zdjęcie</button>
        </form>
    </div>
</div>
