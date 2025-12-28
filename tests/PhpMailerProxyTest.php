<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../php/PhpMailerProxy.php';

final class PhpMailerProxyTest extends TestCase
{
    private string $host;
    private int $port;

    protected function setUp(): void
    {
        parent::setUp();
        $this->host = 'mailhog';
        $this->port = 1025;

        $connection = @fsockopen($this->host, $this->port, $errno, $errstr, 1.0);
        if ($connection === false) {
            $this->markTestSkipped('MailHog SMTP endpoint not reachable: ' . $errstr);
        }
        fclose($connection);
    }

    public function testSendMailWithFromSucceeds(): void
    {
        [$result, $message] = sendMailWithFrom(
            'recipient@example.test',
            'Test subject',
            'Test body',
            'website@example.test',
            'reply@example.test'
        );

        $this->assertTrue($result, $message);
    }

    public function testSendMailUsesConfiguredFrom(): void
    {
        global $SMTP_MAIL_FROM;
        $SMTP_MAIL_FROM = 'website@example.test';

        [$result, $message] = sendMail(
            'recipient2@example.test',
            'Another subject',
            'Another body'
        );

        $this->assertTrue($result, $message);
    }
}
