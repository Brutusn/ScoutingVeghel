<?php

require_once("../php/PhpMailerProxy.php");
require_once("config.php");

function sendConfirmEmail($toMail, $name, $time, $distance, $amountOfWalkers)
{
    global $REPLY_ADDRESS;

    $subject = "Aanmelding Jubileumtocht Scouting Veghel";
    $message = nl2br(htmlentities("Beste " . $name . ",
    
    Hierbij de email om jouw aanmelding te bevestigen.
    Het gekozen tijdslot is " . $time . " voor de " . $distance . " km met " . $amountOfWalkers . " deelnemers.
    Voor meer informatie kunt u reageren op deze e-mail.
    
    Met vriendelijke groeten,
    Scouting Veghel"));

    sendMailWithFrom($toMail, $subject, $message, $REPLY_ADDRESS, $REPLY_ADDRESS);
}
