<?php

require_once("../php/PhpMailerProxy.php");
require_once("config.php");

function sendConfirmEmail($toMail, $name, $time, $distance, $amountOfWalkers)
{
    global $REPLY_ADDRESS;
    global $FROM_ADDRESS;

    $subject = "Aanmelding 11-Spelentocht Scouting Veghel";
    $message = nl2br(htmlentities("Beste " . $name . ",

Hierbij de email om jouw aanmelding te bevestigen. 
Wat leuk dat je op zaterdag 9 juli meeloopt met de 11-Spelentocht, ter ere van 75 jaar Scouting Veghel!
Het gekozen tijdvak is " . $time . " voor de " . $distance . " km tocht met " . $amountOfWalkers . " deelnemer(s).
Zorg dat je je precies in dat tijdvak aanmeld bij de startlocatie naast de silo's op de Noordkade.

LET OP: Kinderen die nog op de basisschool zitten kunnen alleen deelnemen onder begeleiding.

Voor meer informatie kunt u reageren op deze e-mail.
    
Met vriendelijke groeten,
Scouting Veghel"));

    sendMailWithFrom($toMail, $subject, $message, $FROM_ADDRESS, $REPLY_ADDRESS);
}
