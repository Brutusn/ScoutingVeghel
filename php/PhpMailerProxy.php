<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "subm/PHPMailer/src/Exception.php";
require "subm/PHPMailer/src/PHPMailer.php";
require 'subm/PHPMailer/src/SMTP.php';

require_once("MAIL2.php");

/**
* Send the mail using the environment configured SMTP settings using PHP Mailer
*
* @param $toMail The mail address to send it to
* @param $subject The subject of the mail
* @param $message The message content of the mail
* @param $fromMail The from address from where the mail is send
* @param $replyToMail [optional] The mail address to reply to
* @return [bool, message]
*/
function sendMail($toMail, $subject, $message, $fromMail, $replyToMail = ""){
  global $SMTP_SERVER;
  global $SMTP_PORT;
  global $SMTP_USER;
  global $SMTP_PASSWORD;

    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);

    $result = false;
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = $SMTP_SERVER;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $SMTP_PORT;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASSWORD;

        //Mail addresses
        $mail->addAddress($toMail);
        $mail->setFrom($fromMail);
        
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