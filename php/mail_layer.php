<?php

require_once("PhpMailerProxy.php");

$MAIL_ADDRESS_VERHUUR = 'website@scoutingveghel.nl';
$MAIL_ADDRESS_WEBSITE = 'website@scoutingveghel.nl';

$VERIFY_BASE_URL = 'https://test.scoutingveghel.nl/php/verhuur-confirm.php?key=';
$HUURVOOORWAARDEN_URL = 'https://test.scoutingveghel.nl/docs/huurvoorwaarden.pdf';
$HUUROVEREENKOMST_BASE_URL = 'https://test.scoutingveghel.nl/php/huurovereenkomst.php?key=';

/**
* Send the confirmation email to Ellen
*
* @param $mail The mail address of the contact of the Huurder
* @param $name The name of the activity
* @return void
*/
function sendConfirmEmailEllen($name, $email){
  global $MAIL_ADDRESS_WEBSITE;
  global $MAIL_ADDRESS_WEBSITE;
  global $MAIL_ADDRESS_VERHUUR;

  $subject = "Reservering blokhut Scouting Veghel bevestigd.";
  $message = "Beste Ellen,\r\n\r\n
  Er is weer een reservering voor de blokhut bevestigd. De resevering is van: " . $name . " (" . $email . ").\r\n
  Met vriendelijke groeten,\r\n
  Website Scouting Veghel";

  sendMailWithFrom($MAIL_ADDRESS_VERHUUR, $subject, $message, $MAIL_ADDRESS_WEBSITE, $MAIL_ADDRESS_WEBSITE);
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

  sendMailWithFrom($toMail, $subject, $message, $MAIL_ADDRESS_VERHUUR, $MAIL_ADDRESS_VERHUUR);
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

  sendMailWithFrom($toMail, $subject, $message, $MAIL_ADDRESS_VERHUUR, $MAIL_ADDRESS_VERHUUR);
}
?>
