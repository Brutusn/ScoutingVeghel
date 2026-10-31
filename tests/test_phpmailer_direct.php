<?php
/**
 * Direct PHPMailer test with MailHog to isolate the issue
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . "/../php/subm/PHPMailer/src/Exception.php";
require __DIR__ . "/../php/subm/PHPMailer/src/PHPMailer.php";
require __DIR__ . '/../php/subm/PHPMailer/src/SMTP.php';
require __DIR__ . '/../php/MAIL2.php';

echo "Testing PHPMailer with MailHog using MAIL2.php settings\n\n";

// Get settings from MAIL2.php
$host = $SMTP_SERVER;
$port = $SMTP_PORT;
$user = $SMTP_USER;
$password = $SMTP_PASSWORD;
$from = $SMTP_MAIL_FROM;
$secure = $SMTP_SECURE;
$autoTls = $SMTP_AUTO_TLS;

echo "Configuration:\n";
echo "  Host: {$host}\n";
echo "  Port: {$port}\n";
echo "  User: {$user}\n";
echo "  Password: " . str_repeat('*', strlen($password)) . "\n";
echo "  From: {$from}\n";
echo "  SMTP_SECURE: " . var_export($secure, true) . "\n";
echo "  SMTP_AUTO_TLS: " . var_export($autoTls, true) . "\n\n";

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "[DEBUG] $str\n";
    };

    // Server settings
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = $port;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $password;
    
    echo "Setting encryption...\n";
    if ($secure !== false) {
        echo "  Setting SMTPSecure to: " . var_export($secure, true) . "\n";
        $mail->SMTPSecure = $secure;
    } else {
        echo "  NOT setting SMTPSecure (left default)\n";
    }
    
    if ($autoTls === false) {
        echo "  Setting SMTPAutoTLS to false\n";
        $mail->SMTPAutoTLS = false;
    } else {
        echo "  NOT setting SMTPAutoTLS (left default)\n";
    }
    
    echo "\nAttempting to send mail...\n\n";

    $mail->setFrom($from);
    $mail->addAddress('test@example.test');
    $mail->Subject = 'PHPMailer Direct Test';
    $mail->Body = 'This is a test message from PHPMailer direct test.';

    $result = $mail->send();
    
    echo "\n\nSUCCESS! Mail sent successfully.\n";
    echo "Check MailHog at http://localhost:18025\n";

} catch (Exception $e) {
    echo "\n\nFAILED! Error: {$mail->ErrorInfo}\n";
    echo "Exception: {$e->getMessage()}\n";
    exit(1);
}
