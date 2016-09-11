<?php

require_once("settings.php");
require_once("verhuur_settings.php");
require_once("db_layer.php");
require_once("date_layer.php");
require_once("mail_layer.php");

session_start();
date_default_timezone_set(TIME_ZONE);

//check if all parameters are present in the request
if (isset($_POST["name"]) && isset($_POST["contactperson"]) && isset($_POST["mailadr"]) && isset($_POST["phone"])
&& isset($_POST["adress"]) && isset($_POST["postcode"]) && isset($_POST["city"])
&& isset($_POST["people"]) && isset($_POST["tArea"]) && isset($_POST["groepcode"])
&& isset($_POST["aankomst-dag"]) && isset($_POST["aankomst-maand"]) && isset($_POST["aankomst-jaar"])
&& isset($_POST["aankomst-uur"]) && isset($_POST["aankomst-minuut"])
&& isset($_POST["vertrek-dag"]) && isset($_POST["vertrek-maand"]) && isset($_POST["vertrek-jaar"])
&& isset($_POST["vertrek-uur"]) && isset($_POST["vertrek-minuut"])
) {

  //Store all variables and trim them
  $naam = trim(strip_tags($_POST["name"]), " \n");
  $contact = trim(strip_tags($_POST["contactperson"]), " \n");
  $mail = trim(strip_tags($_POST["mailadr"]), " \n");
  $telefoon = trim(strip_tags($_POST["phone"]), " \n");
  $adres = trim(strip_tags($_POST["adress"]), " \n");
  $postcode = trim(strip_tags($_POST["postcode"]), " \n");
  $plaats = trim(strip_tags($_POST["city"]), " \n");
  $aantalPers = trim(strip_tags($_POST["people"]), " \n");
  $area = trim(strip_tags($_POST["tArea"]), " \n");
  $groepscode = trim(strip_tags($_POST["groepcode"]), " \n");
  $aankomstdag = trim(strip_tags($_POST["aankomst-dag"]), " \n");
  $aankomstmaand = trim(strip_tags($_POST["aankomst-maand"]), " \n");
  $aankomstjaar = trim(strip_tags($_POST["aankomst-jaar"]), " \n");
  $aankomstuur = trim(strip_tags($_POST["aankomst-uur"]), " \n");
  $aankomstminuut = trim(strip_tags($_POST["aankomst-minuut"]), " \n");
  $vertrekdag = trim(strip_tags($_POST["vertrek-dag"]), " \n");
  $vertrekmaand = trim(strip_tags($_POST["vertrek-maand"]), " \n");
  $vertrekjaar = trim(strip_tags($_POST["vertrek-jaar"]), " \n");
  $vertrekuur = trim(strip_tags($_POST["vertrek-uur"]), " \n");
  $vertrekminuut = trim(strip_tags($_POST["vertrek-minuut"]), " \n");

  // First check if dates are ok and valid, if so build the proper string
  $startSTR = "";
  $endSTR = "";
  $alreadyReserved = False;
  //Convert the month name to month number
  $aankomstmaandNummer = getMonthNumber($aankomstmaand);
  $vertrekmaandNummer = getMonthNumber($vertrekmaand);
  if($aankomstmaandNummer === -1 || $vertrekmaandNummer === -1) {
    incompleteData("aankomsts- en/of vertrekmaand");
  }
  if (!validDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag, $aankomstuur, $aankomstminuut)
  || !validDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag, $vertrekuur, $vertrekminuut)) {
    //Wrong dates, so indicate that
    incompleteData("aankomst- en/of vertrekdatum");
  }

  //Dates valid, so create the actual string
  $start = new DateTime();
  $start->setDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag);
  $start->setTime($aankomstuur, $aankomstminuut);
  $startSTR = $start->format(DATE_TIME_FORMAT);
  $end = new DateTime();
  $end->setDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag);
  $end->setTime($vertrekuur, $vertrekminuut);
  $endSTR = $end->format(DATE_TIME_FORMAT);
  //create max end date of reservation
  $maxEndDate = new DateTime();
  $maxEndDate->setDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag);
  $maxEndDate->setTime($aankomstuur, $aankomstminuut);
  date_add($maxEndDate, date_interval_create_from_date_string(MAX_AANTAL_OVERNACHTINGEN . ' days'));

  // Make sure the start is before the end
  if ($start > $end){
    incompleteData("aankomstdatum is na de vertrekdatum");
  }

  // check if the number of days the reservation is is more than the max number of days (if more than one day reservation)
  if ($end > $maxEndDate){
    invalidDates();
  }

  // Check if the numbers of persons is avlid
  if ($aantalPers < MIN_AANTAL_PERSONEN || $aantalPers > MAX_AANTAL_PERSONEN) {
    invalidNumberOfPersons();
  }

  // Check if there are already Reservations during the time frame and if so indicate this for the success message
  if (alreadyReserved($start, $end) == True) {
    $alreadyReserved = True;
  }

  //First create Huurder
  $hid = -1;
  $groep = "NULL";
  //Check if either the groepscode is filled in (so made by one of our own groups) or if it is filled in by an external party
  if ($groepscode != "" && $area != "" && $aantalPers != "") {
    //Get information based on the group code and process the request
    $hid = getHuurderIDFromCode($groepscode);
    if ($hid == -1){
      incompleteData("de groepscode is incorrect");
    }
    $info = getInfoFromHid($hid);
    $naam = $info[0];
    $mail = $info[1];
    $groep = $naam;
    $contact = $naam;

  } elseif ($naam != "" && $contact != "" && filter_var($mail, FILTER_VALIDATE_EMAIL)
  && $telefoon != "" && $adres != "" && $postcode != "" && $plaats != ""
  && $aantalPers != "" && $area != "") {
    //Filled in by external party (not own group)
    $hid = getHuurder($naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats);
  } else { //Some empty fields
    missingData();
  }

  //Validate huurderID that was fetched
  if($hid === -1) {errorDatabase();}

  //Then create the Reservering
  $rid = getReservering($area, $startSTR, $endSTR, $aantalPers);
  if($rid === -1) {errorDatabase();}

  //Then create the link between de verhuurder and the reservering (the actual verhuring)
  createVerhuring($hid, $rid, $groep);
  $hashEmail = getConfirm($hid, $rid);
  if($hashEmail === "error") {errorDatabase();}

  //Then send confirmation email to verhuurder with confirm string
  sendConfirmEmail($mail, $contact, $hashEmail, $naam, $startSTR, $endSTR, $aantalPers);

  //Indicate succes
  if (!$alreadyReserved) {
    succesfullReservation();
  } else {
    succesfullReservationAlreadyReserved();
  }
} else {//one of the fields was not set in the POST request
  missingData();
}

/**
* Shows the incomplete message and exits this script
*/
function incompleteData($invalidData){
  echo "Niet alle velden zijn correct ingevuld. Specifiek de velden: $invalidData.";
  header('HTTP/1.1 400 Bad Request');
  exit;
}

/**
* Shows the missing data message and exits this script
*/
function missingData(){
  echo "Niet alle velden zijn ingevuld.";
  header('HTTP/1.1 400 Bad Request');
  exit;
}

/**
* Shows the invalid dates messages and exits the script
*/
function invalidDates(){
  echo "De duur van de optie mag maximaal " . MAX_AANTAL_OVERNACHTINGEN  . " overnachtingen zijn. Mocht u langer willen huren, stuur dan een vraag m.b.v. het bovenstaande formulier.";
  header('HTTP/1.1 400 Bad Request');
  exit;
}

/**
* Shows the invalid number of people mesasges and exits script
*/
function invalidNumberOfPersons(){
  echo "Het aantal personen moet tussen " . MIN_AANTAL_PERSONEN . " en " . MAX_AANTAL_PERSONEN  . " liggen. Mocht u met meer personen willen gaan, stuur dan een vraag m.b.v. het bovenstaande formulier.";
  header('HTTP/1.1 400 Bad Request');
  exit;
}

/**
* Shows the error messages and exits this script
*/
function errorDatabase(){
  echo "Er is iets fout gegaan, probeer het alsutblieft opnieuw. Als de fout zich blijft voordoen, neem dan contact op met de webmaster.";
  header('HTTP/1.1 400 Bad Request');
  exit;
}

/**
* Shows the messages that the blokhut is already reserved in that time frame and exits this script
*/
function succesfullReservationAlreadyReserved(){
  echo "Er is al een optie op de blokhut tijdens de gewenste periode. Uw optie wordt geregisteerd, maar neemt u alstublieft contact op met de verhuurder. U heeft een bevestigingsemail gehad met instructies hoe u uw aanvraag kan bevestigen.";
  header('HTTP/1.1 200 Ok');
  exit;
}

/**
* Shows the succes message and exits this scripts
*/
function succesfullReservation(){
  echo "We hebben uw aanvraag ontvangen. U heeft een bevestigingsemail gehad met instructies hoe u uw aanvraag kan bevestigen.";
  header('HTTP/1.1 200 Ok');
  exit;
}
?>
