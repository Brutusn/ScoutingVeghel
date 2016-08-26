<?php

/**
 * Send the confirmation email to Ellen
 * 
 * @param $mail The mail address of the contact of the Huurder
 * @param $naam The name of the contact
 * @param $hashEmail The confirmation hash of the Verhuring
 * @return void
 */
function sendConfirmEmailEllen(){
    $toMail = "website@scoutingveghel.nl";//verhuur@scoutingveghel.nl";
    $svmail = "website@scoutingveghel.nl";
    $subject = "Reservering blokhut Scouting Veghel Bevestigd";
    //TODO improve messages
    $message = "Beste Ellen,\r\n\r\n
            Er is weer een reservering voor de blokhut bevestigd. 
            Met vriendelijke groeten,\r\n
            Website Scouting Veghel";
    $headers = "From: Website Scouting Veghel <" . $svmail . ">\r\n";
    $headers .= "Reply-To: " . $svmail;
    mail($toMail, $subject, $message, $headers);
}

/**
 * Send the confirmation email to the Huurder
 * 
 * @param $mail The mail address of the contact of the Huurder
 * @param $naam The name of the contact
 * @param $hashEmail The confirmation hash of the Verhuring
 * @return void
 */
function sendConfirmEmail($mail, $naam, $hashEmail){
    $toMail = $mail;
    $svmail = "verhuur@scoutingveghel.nl";
    $subject = "Aanvraag huren blokhut Scouting Veghel";
    //TODO improve messages
    $message = "Beste " . $naam . ",\r\n\r\n
            Hierbij de email om uw reservering te bevestigen. U bevestigt uw resevrering door op de onderstaande link te klikken: \r\n\r\n
            <a href='http://nieuw.scoutingveghel.nl/php/verhuur-confirm.php?key=" . $hashEmail . "'>http://nieuw.scoutingveghel.nl/php/verhuur-confirm.php?key=" . $hashEmail . "</a>\r\n\r\n
            Met vriendelijke groeten,\r\n
            Verhuurder Scouting Veghel";
    $headers = "From: Verhuur Scouting Veghel <" . $svmail . ">\r\n";
    $headers .= "Reply-To: " . $svmail;
    mail($toMail, $subject, $message, $headers);
}
?>