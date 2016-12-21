<?php

//Settings for poetsrooster
$POETS_ROOSTER = [
  1 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_SCOUTS],
  2 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_WELPEN],
  3 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_VERKENNERS],
  4 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_PLUSSCOUTS],
  5 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_BEVERS],
  6 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_NISHAANI],
  7 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_BESTUUR, MAIL_ADDRESS_GEBE],
  8 => [],
  9 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_EXPLORERS],
  10 => [],
  11 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_KLAVERTJE4],
  12 => ["website@scoutingveghel.nl"],//[MAIL_ADDRESS_STAM]
];

$POETS_TASKS = [
  1 => "Grote zaal dweilen + meubilair vochtig afnemen",
  2 => "Binnenkant keukenkastjes",
  3 => "Grote zaal dweilen",
  4 => "Douches en tegels wc",
  5 => "Grote zaal dweilen",
  6 => "Ramen wassen",
  7 => "Grote zaal dweilen + meubilair vochtig afnemen",
  8 => "-",
  9 => "Grote zaal dweilen + binnenkant keukenkastjes",
  10 => "-",
  11 => "Grote zaal dweilen + spinnen raggen",
  12 => "Ramen wassen"
];

//Determine which month it is
$now = new DateTime();
$month = $now->format('n');
//Determine based ont he month who to send an email and what the specific tasks is
$mails = $POETS_ROOSTER[$month];
$task = $POETS_TASKS[$month];

//sent the email to all emails found
$toMail = implode(", ", $mails);
sendPoetsEmail($toMail, $task);


/**
* Send an email to the poetsers of this month according to the POETSROOSTER
*
* @param $toMail The mail address to wich this email needs to be sendDocuments
* @param $task The specific task for this month
* @return void
*/
function sendPoetsEmail($toMail, $task){
  $subject = "Poetsers van de maand";
  $message = htmlentities("Beste poetsers van de maand,\r\n
  Hierbij een herrinnering dat jullie moeten poetsen deze maand. De taak die deze week extra op de planning staat is: " . $task . ".\r\n
  De checklist is te vinden op onze website: http://nieuw.scoutingveghel.nl/docs/poets_checklist.docx \r\n
  Met vriendelijke groeten,\r\n
  Scouting Veghel\r\n\r\n
  Dit is een automatisch verstuurd bericht en je kunt dus niet reageren op deze email.
  ");
  $headers = "From: Scouting Veghel <noreply@scoutingveghel.nl>\r\n";
  $headers .= "Reply-To: noreply@scoutingveghel.nl\r\n";
  $headers .= "Cc: website@scoutingveghel.nl";
  mail($toMail, $subject, $message, $headers);
}

?>
