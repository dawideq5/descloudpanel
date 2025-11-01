<?php
// Plik: /src/handlers/register_handler.php

require_once __DIR__ . '/../auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/register?error=invalid_request');
}

// Pobierz dane z formularza
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Walidacja
if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($password_confirm)) {
    redirect('/register?error=empty_fields');
}

if ($password !== $password_confirm) {
    redirect('/register?error=password_mismatch');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/register?error=invalid_email');
}

global $pdo;

// Sprawdź, czy email już istnieje
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    redirect('/register?error=email_exists');
}

// Generuj nazwę użytkownika
$username = generate_username($first_name, $last_name);

// Hashuj hasło
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Wstaw nowego użytkownika do bazy danych
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$username, $email, $password_hash, $first_name, $last_name]);

// Przekieruj do logowania
redirect('/login?success=registered');
