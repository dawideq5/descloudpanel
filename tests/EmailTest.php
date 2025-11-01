<?php

use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../config/boot.php';
require_once __DIR__ . '/../src/functions.php';

class EmailTest extends TestCase
{
    public function testGetMailer()
    {
        $mailer = getMailer();
        $this->assertInstanceOf(PHPMailer::class, $mailer);
        $this->assertEquals('smtp.home.pl', $mailer->Host);
        $this->assertTrue($mailer->SMTPAuth);
        $this->assertEquals('noreply@descloud.pl', $mailer->Username);
    }
}
