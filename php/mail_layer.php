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
  $message = "Beste Ellen,\r\n\r\n
  Er is weer een reservering voor de blokhut bevestigd. De resevering is van: " . $name . " (" . $email . ").\r\n
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
  $message = htmlentities("Beste " . $naam . ",\r\n
  Hierbij de email om uw reservering te bevestigen. U bevestigt uw resevering door op de onderstaande link te klikken: \r\n
  http://nieuw.scoutingveghel.nl/php/verhuur-confirm.php?key=" . $hashEmail . "\r\n\r\n
  De activiteit waavoor u een optie op de blokhut genomen heeft is: " . $activity . ", " .
  (($endSTR != "") ? " van " . $startSTR . " tot " . $endSTR  : " op " . $startSTR)
  . " en voor " . $aantalPers . " personen.\r\n\r\n
  Als deze mail verkeerd geaddresseerd is, of wanneer je de optie niet om wilt zetten naar een reservering, hoef je niets te doen. Binnen 2 weken zal deze komen te vervallen. \r\n
  Voor meer informatie kunt u reageren op deze e-mail.\r\n\r\n
  Met vriendelijke groeten,\r\n
  Verhuurder Scouting Veghel");
  $headers = "From: Verhuur Scouting Veghel <" . $svmail . ">\r\n";
  $headers .= "Reply-To: " . $svmail;
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
function sendDocuments($name, $email, $confirm_key){
  //STUB so far
  //TODO implement

  $toMail = $email;
  $subject = "Bevestiging optie blokhut Scouting Veghel";
  $message = htmlentities("Beste " . $name . ",\r\n
  Hierbij de bevestiging van uw reservering.".
  //De huurvoorwaarden en de huurovereenkomst zijn als bijlage toegevoegd.
  "U kunt de huurvoorwaarden vinden op onze site: http://nieuw.scoutingveghel.nl/docs/huurvoorwaarden.pdf \r\n
  De huurovereenkomst is te hier te vinden: http://nieuw.scoutingveghel.nl/php/huurovereenkomst.php?key=" . $confirm_key . "\r\n".
  "De huurovereenkomst graag doorlezen en ondertekenen.\r\n\r\n
  Met vriendelijke groeten,\r\n
  Verhuurder Scouting Veghel");
  $headers = "From: Verhuur Scouting Veghel <" . $svmail . ">\r\n";
  $headers .= "Reply-To: " . $svmail;
  mail($toMail, $subject, $message, $headers);
  //attachmentEmail($toMail, $subject, $message, "../docs/", "huurvoorwaarden.pdf");
}

/**
 *
 *
 *
 *
 *
 *
 *
 * @see http://stackoverflow.com/questions/12301358/send-attachments-with-php-mail
 */
function attachmentEmail($mailto, $subject, $message, $path, $filename){
  $namefrom = "Verhuur Scouting Veghel";
  $mailfrom = "verhuur@scoutingveghel.nl";

  $file = $path . "/" . $filename;
  $content = file_get_contents($file);
  $content = chunk_split(base64_encode($content));

  // a random hash will be necessary to send mixed content
  $separator = md5(time());

  // carriage return type (we use a PHP end of line constant)
  $eol = PHP_EOL;

  // main header (multipart mandatory)
  $headers = "From: " . $namefrom . " <" . $mailfrom . ">" . $eol;
  $headers .= "MIME-Version: 1.0" . $eol;
  $headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
  $headers .= "Content-Transfer-Encoding: 7bit" . $eol;
  $headers .= "This is a MIME encoded message." . $eol;

  // message
  $headers .= "--" . $separator . $eol;
  $headers .= "Content-Type: text/plain; charset=\"iso-8859-1\"" . $eol;
  $headers .= "Content-Transfer-Encoding: 8bit" . $eol;
  $headers .= $message . $eol;

  // attachment
  $headers .= "--" . $separator . $eol;
  $headers .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
  $headers .= "Content-Transfer-Encoding: base64" . $eol;
  $headers .= "Content-Disposition: attachment" . $eol;
  $headers .= $content . $eol;
  $headers .= "--" . $separator . "--";

  mail($mailto, $subject, "", $headers);
  //SEND Mail
  //if (mail($mailto, $subject, "", $headers)) {
    //echo "mail send ... OK"; // or use booleans here
  //} else {
    //echo "mail send ... ERROR!";
  //}
}
?>
