<?php
require_once("db_layer.php");
require_once("date_layer.php");
require_once("mail_layer.php");
//require_once("debug_layer.php");

session_start();
date_default_timezone_set('Europe/Paris');

//check if all parameters are present in the request
//TODO make the names better for the inputs (not consitent with the actual site right now)
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
    //Convert the month name to month number
    $aankomstmaandNummer = getMonthNumber($aankomstmaand);
    $vertrekmaandNummer = getMonthNumber($vertrekmaand);
    if($aankomstmaandNummer === -1 || $vertrekmaandNummer === -1) {
        incompleteData();
    }
    if (!validDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag, $aankomstuur, $aankomstminuut) 
        || !validDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag, $vertrekuur, $vertrekminuut)) {
        //Wrong dates, so indicate that
        incompleteData();
    }

    //Dates valid, so create the actual string
    $start = new DateTime();
    $start->setDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag);
    $start->setTime($aankomstuur, $aankomstminuut);
    $startSTR = $start->format("Y-m-d H:i:s");
    $end = new DateTime();
    $end->setDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag);
    $end->setTime($vertrekuur, $vertrekminuut);
    $endSTR = $end->format("Y-m-d H:i:s");

    // Make sure the start is before the end
    if ($start > $end){
        incompleteData();
    }

    //First check if either the groepscode is filled in (so made by one of our own groups) or if it is filled in by an external party
    if ($groepscode != "" && $area != "") {
        //Get information based on the group code and process the request
        //TODO implement
        echo "The functionaliteit om met de groepscode in te loggen is nog niet geimplementeerd.";
        header('HTTP/1.1 200 Ok');
        exit;
    } elseif ($naam != "" && $contact != "" && filter_var($mail, FILTER_VALIDATE_EMAIL) 
        && $telefoon != "" && $adres != "" && $postcode != "" && $plaats != "" 
        && $aantalPers != "" && $area) {
        //Filled in by external party (not own group)

        //First create Huurder
        $hid = getHuurder($naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats);
        if($hid === -1) {errorDatabase();}

        //Then create the Reservering
        $rid = getReservering($area, $startSTR, $endSTR, $aantalPers);
        if($rid === -1) {errorDatabase();}

        //Then create the link between de verhuurder and the reservering (the actual verhuring)
        createVerhuring($hid, $rid,"NULL");
        $hashEmail = getConfirm($hid, $rid);
        if($hashEmail === "error") {errorDatabase();}

        //Then send confirmation email to verhuurder with confirm string
        sendConfirmEmail($mail, $naam, $hashEmail);

        //Indicate succes
        succesfullReservation();
    } else { //No groepscode and all empty fields
        incompleteData();
    }
} else {//one of the fields was not set in the POST request
    incompleteData();
}

/**
 * Shows the incomplete message and exits this script
 */
function incompleteData(){
    echo "Niet alle velden zijn (correct) ingevuld.";
    header('HTTP/1.1 400 Bad Request');
    exit;
}

/**
 * Shows the error messages and exits this script
 */
function errorDatabase(){
    echo "Er is iets fout gegaan, probeer het alsutblieft opnieuw. Als de fout aanhoudt, neem dan contact op met de webmaster.";
    header('HTTP/1.1 400 Bad Request');
    exit;
}

/**
 * Shows the succes message and exits this scripts
 */
function succesfullReservation(){
    echo "We hebben uw aanvraag ontvangen. U heeft een bevestigings-email gehad met instructies hoe u uw aanvraag kan bevestigen.";
    header('HTTP/1.1 200 Ok');
    exit;
}
?>