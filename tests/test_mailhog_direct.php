<?php
/**
 * Direct MailHog connection test without PHPMailer
 * This verifies MailHog is accessible and accepting SMTP connections
 */

$host = getenv('SV_SMTP_HOST') ?: 'mailhog';
$port = (int) (getenv('SV_SMTP_PORT') ?: 1025);

echo "Testing direct connection to MailHog at {$host}:{$port}\n\n";

// Test 1: Can we reach the SMTP port?
echo "Test 1: Port reachability\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
if (!$socket) {
    echo "FAILED: Cannot connect to {$host}:{$port} - {$errstr} (errno: {$errno})\n";
    exit(1);
}
echo "SUCCESS: Connected to {$host}:{$port}\n\n";

// Test 2: Can we get SMTP greeting?
echo "Test 2: SMTP greeting\n";
$response = fgets($socket);
echo "Response: " . $response;
if (!str_starts_with($response, '220')) {
    echo "FAILED: Expected 220 greeting\n";
    fclose($socket);
    exit(1);
}
echo "SUCCESS: Got SMTP greeting\n\n";

// Test 3: Can we send EHLO?
echo "Test 3: EHLO command\n";
fwrite($socket, "EHLO localhost\r\n");
$response = '';
while ($line = fgets($socket)) {
    $response .= $line;
    if (preg_match('/^250 /', $line)) {
        break;
    }
}
echo "Response:\n" . $response;
if (!str_contains($response, '250')) {
    echo "FAILED: Expected 250 response\n";
    fclose($socket);
    exit(1);
}
echo "SUCCESS: EHLO accepted\n\n";

// Test 4: Can we send a simple email?
echo "Test 4: Sending test email\n";
fwrite($socket, "MAIL FROM:<test@example.test>\r\n");
$response = fgets($socket);
echo "MAIL FROM response: " . $response;

fwrite($socket, "RCPT TO:<recipient@example.test>\r\n");
$response = fgets($socket);
echo "RCPT TO response: " . $response;

fwrite($socket, "DATA\r\n");
$response = fgets($socket);
echo "DATA response: " . $response;

fwrite($socket, "Subject: Test from direct connection\r\n\r\nThis is a test message.\r\n.\r\n");
$response = fgets($socket);
echo "Message response: " . $response;

fwrite($socket, "QUIT\r\n");
$response = fgets($socket);
echo "QUIT response: " . $response;

fclose($socket);
echo "\nSUCCESS: All tests passed! MailHog is working correctly.\n";
echo "Check MailHog web interface at http://localhost:8025 to see the test message.\n";
