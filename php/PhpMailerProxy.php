<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/subm/PHPMailer/src/Exception.php";
require __DIR__ . "/subm/PHPMailer/src/PHPMailer.php";
require __DIR__ . '/subm/PHPMailer/src/SMTP.php';

$mailConfigPath = defined('MAIL_CONFIG_PATH')
    ? MAIL_CONFIG_PATH
    : __DIR__ . '/MAIL2.php';
require_once $mailConfigPath;

/**
* Send the mail using the environment configured SMTP settings using PHP Mailer
*
* @param $toMail The mail address to send it to
* @param $subject The subject of the mail
* @param $message The message content of the mail
* @param $replyToMail [optional] The mail address to reply to
* @return [bool, message]
*/
function sendMail($toMail, $subject, $message, $replyToMail = ""){
    global $SMTP_MAIL_FROM;
     // Must be the one configured at the SMTP server (e.g. Office365 mailbox) that is authenticated to sent mails
     return sendMailWithFrom($toMail, $subject, $message, $SMTP_MAIL_FROM, $replyToMail);
}

/**
* INTERNAL use only, due to the authorized fromMail parameter
*
* @param $toMail The mail address to send it to
* @param $subject The subject of the mail
* @param $message The message content of the mail
* @param $fromMail THe mail address from which to send it. Note that this must be an authorized address.
* @param $replyToMail The mail address to reply to
* @return [bool, message]
*/
function sendMailWithFrom($toMail, $subject, $message, $fromMail, $replyToMail){
  global $SMTP_SERVER;
  global $SMTP_PORT;
  global $SMTP_USER;
  global $SMTP_PASSWORD;
  global $SMTP_AUTO_TLS;
  global $SMTP_SECURE;
  global $PHPMAILER_DEBUG;

    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    $result = false;
    try {
        //Server settings
        $mail->isSMTP();

        // Enable debug output if requested (for testing)
        $debugLevel = $PHPMAILER_DEBUG ?? 0;
        if ($debugLevel !== false && $debugLevel !== '' && $debugLevel !== null && $debugLevel > 0) {
            $mail->SMTPDebug = (int)$debugLevel;
            echo "PHPMailer debug enabled, level: " . (int)$debugLevel . "\n";
            $mail->Debugoutput = function($str, $level) {
                echo "[PHPMailer] $str\n";
            };
        }

        $mail->Host = $SMTP_SERVER;
        $mail->Port = $SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASSWORD;

        // Set encryption if configured (default to STARTTLS for production)
        if (isset($SMTP_SECURE) && $SMTP_SECURE !== false) {
            $mail->SMTPSecure = $SMTP_SECURE;
        } elseif (!isset($SMTP_SECURE)) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        // When SMTP_SECURE is explicitly false, don't set SMTPSecure at all

        // Allow disabling auto-TLS for mail servers like MailHog
        if (isset($SMTP_AUTO_TLS) && $SMTP_AUTO_TLS === false) {
            $mail->SMTPAutoTLS = false;
        }

        // Must be the one configured at the SMTP server (e.g. Office365 mailbox) that is authenticated to sent mails
        $mail->setFrom($fromMail);

        //Mail addresses
        $mail->addAddress($toMail);
        
        if(!empty($replyToMail)){
            $mail->addReplyTo($replyToMail);
        }

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        //$mail->SMTPDebug  = 3;
        //$mail->Debugoutput = function($str, $level) {echo "debug level $level; message: $str <br>";};

        $result = $mail->send();
        return array($result, 'Message has been sent');
    } catch (Exception $e) {
        return array($result, "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

?>