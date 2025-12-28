<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../php/MAIL2.php';
define('PHPMAILER_PROXY_PATH', __DIR__ . '/../php/PhpMailerProxy.php');
require_once __DIR__ . '/../php/mail_layer.php';

final class MailLayerTest extends TestCase
{
    private string $host;
    private int $smtpPort;
    private int $httpPort;

    protected function setUp(): void
    {
        parent::setUp();
        global $SMTP_SERVER, $SMTP_PORT;
        $this->host = $SMTP_SERVER;
        $this->smtpPort = $SMTP_PORT;
        $this->httpPort = 8025;

        $connection = @fsockopen($this->host, $this->smtpPort, $errno, $errstr, 1.0);
        if ($connection === false) {
            $this->markTestSkipped('MailHog SMTP endpoint not reachable: ' . $errstr);
        }
        fclose($connection);

        $this->flushInbox();
    }

    private function flushInbox(): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE',
            ],
        ]);
        @file_get_contents($this->getMailhogUrl('/api/v1/messages'), false, $context);
    }

    private function getMailhogUrl(string $path): string
    {
        return sprintf('http://%s:%d%s', $this->host, $this->httpPort, $path);
    }

    private function waitForMessageWithSubject(string $subject): array
    {
        $deadline = microtime(true) + 5.0;
        do {
            $messages = $this->fetchMessages();
            foreach ($messages as $message) {
                $headers = $message['Content']['Headers'] ?? [];
                if (isset($headers['Subject'][0]) && $headers['Subject'][0] === $subject) {
                    return $message;
                }
            }
            usleep(200000);
        } while (microtime(true) < $deadline);

        $this->fail('Mail with subject "' . $subject . '" not received in MailHog');
    }

    private function fetchMessages(): array
    {
        $response = @file_get_contents($this->getMailhogUrl('/api/v2/messages?limit=50'));
        if ($response === false) {
            $this->fail('Unable to query MailHog HTTP API');
        }

        $payload = json_decode($response, true);
        return $payload['items'] ?? [];
    }

    public function testSendConfirmEmailEllenSendsNotification(): void
    {
        $subject = 'Reservering blokhut Scouting Veghel bevestigd.';
        sendConfirmEmailEllen('Activiteit Test', 'huurder@example.test');
        $message = $this->waitForMessageWithSubject($subject);
        $this->assertStringContainsString('Activiteit Test', $message['Content']['Body'] ?? '');
    }

    public function testSendConfirmEmailSendsToTenant(): void
    {
        $subject = 'Aanvraag huren blokhut Scouting Veghel';
        $hash = 'testhash-' . uniqid();
        sendConfirmEmail('huurder@example.test', 'Huurder', $hash, 'Kamp', '2025-01-01', '2025-01-03', 25);
        $message = $this->waitForMessageWithSubject($subject);
        $this->assertStringContainsString($hash, $message['Content']['Body'] ?? '');
    }

    public function testSendDocumentsProvidesLinks(): void
    {
        $subject = 'Bevestiging optie blokhut Scouting Veghel';
        sendDocuments('Huurder', 'huurder@example.test', 'confirm-key');
        $message = $this->waitForMessageWithSubject($subject);
        $this->assertStringContainsString('confirm-key', $message['Content']['Body'] ?? '');
    }
}
