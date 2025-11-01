<?php

use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    public function testInvalidUsernameLogin()
    {
        // Simulate a POST request to the login handler
        $_POST['username'] = 'nonexistentuser';
        $_POST['password'] = 'wrongpassword';

        // Include the login handler
        // This will have headers that we can't test directly,
        // so we will check the outcome by checking the headers list.
        require __DIR__ . '/../src/handlers/login_handler.php';

        // Check if the redirect header is set correctly
        $headers = xdebug_get_headers();
        $this->assertContains('Location: /login?error=1', $headers);
    }
}