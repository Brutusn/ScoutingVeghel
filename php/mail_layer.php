<?php

/**
 * Send the confirmation email to Ellen
 *
 * @param $mail The mail address of the contact of the Huurder
 * @param $name The name of the activity
 * @return void
 */
function sendConfirmEmailEllen($name, $email){
    $toMail = "website@scoutingveghel.nl";//verhuur@scoutingveghel.nl";
    $svmail = "website@scoutingveghel.nl";
    $subject = "Reservering blokhut Scouting Veghel bevestigd.";
    //TODO improve messages
    $message = "Beste Ellen,\r\n\r\n
            Er is weer een reservering voor de blokhut bevestigd. De resevering is van " . $name . " (" . $email . ").\r\n
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
 * @param $activity The name of the activity
 * @param $StartSTR The start of the activity in string format
 * @param $endStr The end of the activity in string format
 * @param $aantalPers The number of person involved int he activity
 * @return void
 */
function sendConfirmEmail($mail, $naam, $hashEmail, $activity, $startSTR, $endSTR, $aantalPers){
    $toMail = $mail;
    $svmail = "verhuur@scoutingveghel.nl";
    $subject = "Aanvraag huren blokhut Scouting Veghel";
    //TODO improve messages
    $message = htmlentities("Beste " . $naam . ",\r\n
            Hierbij de email om uw reservering te bevestigen. U bevestigt uw resevering door op de onderstaande link te klikken: \r\n
            http://nieuw.scoutingveghel.nl/php/verhuur-confirm.php?key=" . $hashEmail . "\r\n\r\n
            De activiteit waavoor u een optie op de blokhut genomen heeft is " . $activity .
            (($endSTR != "") ? " van " . $startSTR . " tot " . $endSTR  : " op " . $startSTR)
             . " voor " . $aantalPers . " personen.\r\n\r\n
            Als deze mail verkeerd geaddresseerd is, of wanneer je de optie niet om wilt zetten naar een reservering, hoef je niets te doen. Binnen 2 weken zal deze komen te vervallen. \r\n
            Voor meer informatie kunt u reageren op deze e-mail.\r\n\r\n
            Met vriendelijke groeten,\r\n
            Verhuurder Scouting Veghel");
    $headers = "From: Verhuur Scouting Veghel <" . $svmail . ">\r\n";
    $headers .= "Reply-To: " . $svmail;
    mail($toMail, $subject, $message, $headers);
}


function sendDocuments($name, $email, $verhuring_id){
  //STUB so far
  //TODO implement
}
?>
