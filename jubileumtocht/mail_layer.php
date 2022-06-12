<?php

require_once("../php/PhpMailerProxy.php");
require_once("config.php");

function sendConfirmEmail($toMail, $name, $time, $distance, $amountOfWalkers)
{
    global $REPLY_ADDRESS;

    $subject = "Aanmelding Jubileumtocht Scouting Veghel";
    $message = htmlentities("Beste " . $name . ",\r\n
    Hierbij de email om jouw aanmelding te bevestigen.\r\n
    Het gekozen tijdslot is " . $time . " voor de " . $distance . " km met " . $amountOfWalkers . " deelnemers.\r\n

    Voor meer informatie kunt u reageren op deze e-mail.\r\n\r\n
    Met vriendelijke groeten,\r\n
    Scouting Veghel");

    sendMailWithFrom($toMail, $subject, $message, $REPLY_ADDRESS, $REPLY_ADDRESS);
}
