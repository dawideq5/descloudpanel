<?php

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        // Use an in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create the users table
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                username TEXT,
                `first name` TEXT,
                last_name TEXT,
                email TEXT,
                password_hash TEXT,
                phone_private TEXT,
                phone_work TEXT,
                email_work TEXT,
                session_token TEXT,
                new_email TEXT,
                email_change_token TEXT,
                email_change_expires TEXT,
                mfa_totp_secret TEXT,
                mfa_totp_enabled INTEGER,
                mfa_email_enabled INTEGER
            )
        ");

        // Create a dummy user for testing
        $this->pdo->exec("
            INSERT INTO users (id, username, `first name`, last_name, email, password_hash)
            VALUES (1, 'testuser', 'Test', 'User', 'test@example.com', 'somehash')
        ");
    }

    public function testProfileUpdate()
    {
        global $pdo;
        $pdo = $this->pdo;

        // Simulate a logged-in user
        $_SESSION['user_id'] = 1;

        // Simulate a POST request to the profile handler
        $_POST['first_name'] = 'Updated First';
        $_POST['last_name'] = 'Updated Last';
        $_POST['phone_private'] = '123456789';
        $_POST['phone_work'] = '987654321';
        $_POST['email_work'] = 'work@example.com';

        // Include the profile handler
        require __DIR__ . '/../src/handlers/settings_profile_handler.php';

        // Check if the redirect header is set correctly
        $headers = xdebug_get_headers();
        $this->assertContains('Location: /settings?tab=profile', $headers);

        // Check if the database was updated
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('Updated First', $user['first name']);
        $this->assertEquals('Updated Last', $user['last_name']);
        $this->assertEquals('123456789', $user['phone_private']);
        $this->assertEquals('987654321', $user['phone_work']);
        $this->assertEquals('work@example.com', $user['email_work']);
    }
}