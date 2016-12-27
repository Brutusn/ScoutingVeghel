<?php

$MAIL_ADDRESS_VERHUUR = 'website@scoutingveghel.nl';
$MAIL_ADDRESS_WEBSTIE = 'website@scoutingveghel.nl';

$VERIFY_BASE_URL = 'http://nieuw.scoutingveghel.nl/php/verhuur-confirm.php?key=';
$HUURVOOORWAARDEN_URL = 'http://nieuw.scoutingveghel.nl/docs/huurvoorwaarden.pdf';
$HUUROVEREENKOMST_BASE_URL = 'http://nieuw.scoutingveghel.nl/php/huurovereenkomst.php?key=';

$FROM_HEADER_VERHUUR = 'Scouting Veghel Verhuur <' . $MAIL_ADDRESS_VERHUUR . '>';
$FROM_HEADER_WEBSITE = 'Scouting Veghel Website <' . $MAIL_ADDRESS_WEBSTIE . '>';
$FROM_HEADER_NO_REPLY = 'Scouting Veghel <noreply@scoutingveghel.nl>';

/**
* Send the confirmation email to Ellen
*
* @param $mail The mail address of the contact of the Huurder
* @param $name The name of the activity
* @return void
*/
function sendConfirmEmailEllen($name, $email){
  global $FROM_HEADER_WEBSITE;
  global $MAIL_ADDRESS_WEBSITE;
  global $MAIL_ADDRESS_VERHUUR;

  $subject = "Reservering blokhut Scouting Veghel bevestigd.";
  $message = "Beste Ellen,\r\n\r\n
  Er is weer een reservering voor de blokhut bevestigd. De resevering is van: " . $name . " (" . $email . ").\r\n
  Met vriendelijke groeten,\r\n
  Website Scouting Veghel";
  $headers = "From: " . $FROM_HEADER_WEBSITE ."\r\n";
  $headers .= "Reply-To: " . $MAIL_ADDRESS_WEBSITE;
  mail($MAIL_ADDRESS_VERHUUR, $subject, $message, $headers);
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
function sendConfirmEmail($toMail, $naam, $hashEmail, $activity, $startSTR, $endSTR, $aantalPers){
  global $FROM_HEADER_VERHUUR;
  global $MAIL_ADDRESS_VERHUUR;
  global $VERIFY_BASE_URL;

  $subject = "Aanvraag huren blokhut Scouting Veghel";
  $message = htmlentities("Beste " . $naam . ",\r\n
  Hierbij de email om uw reservering te bevestigen. U bevestigt uw resevering door op de onderstaande link te klikken: \r\n
  " . $VERIFY_BASE_URL . $hashEmail . "\r\n\r\n
  De activiteit waavoor u een optie op de blokhut genomen heeft is: " . $activity . ", " .
  (($endSTR != "") ? " van " . $startSTR . " tot " . $endSTR  : " op " . $startSTR)
  . " en voor " . $aantalPers . " personen.\r\n\r\n
  Als deze mail verkeerd geaddresseerd is, of wanneer je de optie niet om wilt zetten naar een reservering, hoef je niets te doen. Binnen 2 weken zal deze komen te vervallen. \r\n
  Voor meer informatie kunt u reageren op deze e-mail.\r\n\r\n
  Met vriendelijke groeten,\r\n
  Verhuurder Scouting Veghel");
  $headers = "From: " . $FROM_HEADER_VERHUUR . "\r\n";
  $headers .= "Reply-To: " . $MAIL_ADDRESS_VERHUUR;
  mail($toMail, $subject, $message, $headers);
}

/**
* Send the email after the confirmation link is clicked with the documents
*
* @param $name The name of the huurder
* @param $email The email fo the huurder
* @param $confirm_key The id of the verhuring this option is about
* @return void
*/
function sendDocuments($name, $toMail, $confirm_key){
  global $FROM_HEADER_VERHUUR;
  global $MAIL_ADDRESS_VERHUUR;
  global $HUURVOOORWAARDEN_URL;
  global $HUUROVEREENKOMST_BASE_URL;

  $subject = "Bevestiging optie blokhut Scouting Veghel";
  $message = htmlentities("Beste " . $name . ",\r\n
  Hierbij de bevestiging van uw reservering.".
  //De huurvoorwaarden en de huurovereenkomst zijn als bijlage toegevoegd.
  "U kunt de huurvoorwaarden vinden op onze site: " . $HUURVOOORWAARDEN_URL . " \r\n
  De huurovereenkomst is hier te vinden: " . $HUUROVEREENKOMST_BASE_URL . $confirm_key . "\r\n\r\n
  De huurovereenkomst graag doorlezen en ondertekenen.\r\n\r\n
  Met vriendelijke groeten,\r\n
  Verhuurder Scouting Veghel");
  $headers = "From: " . $FROM_HEADER_VERHUUR . "\r\n";
  $headers .= "Reply-To: " . $MAIL_ADDRESS_VERHUUR;
  mail($toMail, $subject, $message, $headers);
}
?>
