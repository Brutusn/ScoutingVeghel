<?php

require_once("verhuur_settings.php");
require_once("db_layer.php");
require_once("date_layer.php");
require_once("mail_layer.php");

session_start();
date_default_timezone_set('Europe/Paris');

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

  // Save all data in an array that is used for logging. 
  $allData = array($naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats, $aantalPers, $area, $groepscode, $aankomstjaar, $aankomstmaand, $aankomstdag, $aankomstuur, $aankomstminuut, $vertrekjaar, $vertrekmaand, $vertrekdag, $vertrekuur, $vertrekminuut);

  // First check if dates are ok and valid, if so build the proper string
  $startSTR = "";
  $endSTR = "";
  $alreadyReserved = False;
  //Convert the month name to month number
  $aankomstmaandNummer = getMonthNumber($aankomstmaand);
  $vertrekmaandNummer = getMonthNumber($vertrekmaand);
  if($aankomstmaandNummer === -1 || $vertrekmaandNummer === -1) {
    error_log("Mont number is -1: " . $aankomstmaand . " " . $vertrekmaand);
    logAllData($allData);
    incompleteData("aankomsts- en/of vertrekmaand");
  }
  if (!validDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag, $aankomstuur, $aankomstminuut)
  || !validDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag, $vertrekuur, $vertrekminuut)) {
    error_log("Invalid dates: " . $aankomstjaar ."-". $aankomstmaandNummer ."-". $aankomstdag ." ". $aankomstuur .":". $aankomstminuut ." ; ". $vertrekjaar ."-". $vertrekmaandNummer ."-". $vertrekdag ." ". $vertrekuur .":". $vertrekminuut);
    logAllData($allData);
    //Wrong dates, so indicate that
    incompleteData("aankomst- en/of vertrekdatum");
  }

  //Dates valid, so create the actual string
  $start = new DateTime();
  $start->setDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag);
  $start->setTime($aankomstuur, $aankomstminuut);
  $startSTR = $start->format('Y-m-d H:i:s');
  $end = new DateTime();
  $end->setDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag);
  $end->setTime($vertrekuur, $vertrekminuut);
  $endSTR = $end->format('Y-m-d H:i:s');
  //create max end date of reservation
  $maxEndDate = new DateTime();
  $maxEndDate->setDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag);
  $maxEndDate->setTime($aankomstuur, $aankomstminuut);
  date_add($maxEndDate, date_interval_create_from_date_string(MAX_AANTAL_OVERNACHTINGEN . ' days'));
  $maxEndDateSTR = $maxEndDate->format('Y-m-d H:i:s');

  // Make sure the start is before the end
  if ($start > $end){
    error_log("Start date is after end date: " . $startSTR . " > " . $endSTR);
    logAllData($allData);
    incompleteData("aankomstdatum is na de vertrekdatum");
  }

  // check if the number of days the reservation is is more than the max number of days (if more than one day reservation)
  if ($end > $maxEndDate){
    error_log("End date is after maximum date: " . $endSTR . " > " . $maxEndDateSTR);
    logAllData($allData);
    invalidDates();
  }

  // Check if the numbers of persons is avlid
  if ($aantalPers < MIN_AANTAL_PERSONEN || $aantalPers > MAX_AANTAL_PERSONEN) {
    error_log("Number of person is not allowed: " . $aantalPers);
    logAllData($allData);
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
      error_log("Groepscode is incorrect: " . $groepscode);
      //DO not log all data, since we already know the rest of the data is OK
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
    error_log("Some fields were empty.");
    logAllData($allData);
    missingData();
  }

  //Validate huurderID that was fetched
  if($hid === -1) {
    error_log("HuurderID could not be fetched nor created.");
    informOperator();
    logAllData($allData);
    errorDatabase();
  }

  //Then create the Reservering
  $rid = getReservering($area, $startSTR, $endSTR, $aantalPers);
  if($rid === -1) {
    error_log("ReserveringID could not be fetched nor created.");
    informOperator();
    logAllData($allData);
    errorDatabase();
  }

  //Then create the link between the verhuurder and the reservering (the actual verhuring)
  createVerhuring($hid, $rid, $groep);
  $hashEmail = getConfirm($hid, $rid);
  if($hashEmail === "error") {
    error_log("The hash for the email could not be created for HID: " . $hid . " RID: " . $rid);
    informOperator();
    logAllData($allData);
    errorDatabase();
  }

  //Then send confirmation email to verhuurder with confirm string
  sendConfirmEmail($mail, $contact, $hashEmail, $naam, $startSTR, $endSTR, $aantalPers);

  //Indicate succes
  if (!$alreadyReserved) {
    succesfullReservation();
  } else {
    succesfullReservationAlreadyReserved();
  }
} else {//one of the fields was not set in the POST request
  error_log("Incomplete form submitted.");
  logAllData($allData);
  missingData();
}

/**
* Shows the incomplete message and exits this script
*/
function incompleteData($invalidData){
  echo "Niet alle velden zijn correct ingevuld. Specifiek de velden: $invalidData.";
  exit;
}

/**
* Shows the missing data message and exits this script
*/
function missingData(){
  echo "Niet alle velden zijn ingevuld.";
  exit;
}

/**
* Shows the invalid dates messages and exits the script
*/
function invalidDates(){
  echo "De duur van de optie mag maximaal " . MAX_AANTAL_OVERNACHTINGEN  . " overnachtingen zijn. Mocht u langer willen huren, stuur dan een vraag m.b.v. het bovenstaande formulier.";
  exit;
}

/**
* Shows the invalid number of people mesasges and exits script
*/
function invalidNumberOfPersons(){
  echo "Het aantal personen moet tussen " . MIN_AANTAL_PERSONEN . " en " . MAX_AANTAL_PERSONEN  . " liggen. Mocht u met meer personen willen gaan, stuur dan een vraag m.b.v. het bovenstaande formulier.";
  exit;
}

/**
* Shows the error messages and exits this script
*/
function errorDatabase(){
  echo "Er is iets fout gegaan, probeer het alsutblieft opnieuw. Als de fout zich blijft voordoen, neem dan contact op met de webmaster.";
  exit;
}

/**
* Shows the messages that the blokhut is already reserved in that time frame and exits this script
*/
function succesfullReservationAlreadyReserved(){
  echo "Er is al een optie op de blokhut tijdens de gewenste periode. Uw optie wordt geregisteerd, maar neemt u alstublieft contact op met de verhuurder. U heeft een bevestigingsemail gehad met instructies hoe u uw aanvraag kan bevestigen.";
  exit;
}

/**
* Shows the succes message and exits this scripts
*/
function succesfullReservation(){
  echo "We hebben uw aanvraag ontvangen. U heeft een bevestigingsemail gehad met instructies hoe u uw aanvraag kan bevestigen.";
  exit;
}

/**
 * Logs all data to the log file
 */
function logAllData($arrayOfData){
  error_log("All Data filled in is: " . implode("; ", $arrayOfData));
}

/**
 * Inform the operator that there has been a important issue. Mainly used for DB errors. 
 */
function informOperator(){
  error_log("There was an issue with the verhuur form. Please take a look at the log.", 1, "website@scoutingveghel.nl");
}

?>
