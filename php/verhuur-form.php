<?php
require_once("DB2.php");

session_start();
date_default_timezone_set('Europe/Paris');

//ini_set('display_startup_errors',1);
//ini_set('display_errors',1);
//error_reporting(-1);

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
        if($hid === -1) {echo $errorString;}

        //Then create the Reservering
        $rid = getReservering($area, $startSTR, $endSTR, $aantalPers);
        if($rid === -1) {echo $errorString;}

        //Then create the link between de verhuurder and the reservering (the actual verhuring)
        createVerhuring($hid, $rid,"NULL");
        $hashEmail = getConfirm($hid, $rid);
        if($hashEmail === "error") {echo $errorString;}

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
 * Gets the Huurder from the table, if not present it creates it. 
 * 
 * @param $naam The name of the Huurder or its organistation
 * @param $contact The name of the contacperson of the Huurder
 * @param $mail The email adres of the contact
 * @param $telefoon The phone of the contact
 * @param $adres The address of the contact
 * @param $postcode The postal code of the contact
 * @param $plaats The city of the contact
 * @return The id of the Huurder in the database, returns -1 if somehting goes wrong. 
 */
function getHuurder($naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats){
    //First check if the party already exists
    $found = false;
    $huurderid = -1;
    $mysqli = databaseMYSQLi();
    
    // Find the Huurder in the database
    if($stmt_gh = $mysqli->prepare("CALL GetHuurder(?, ?, ?, ?, ?, ?, ?)")){
        $stmt_gh->bind_param("sssssss", $naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats);
        $stmt_gh->execute();
        $stmt_gh->bind_result($id);
        while ($stmt_gh->fetch()) {
            $huurderid = $id;
            $found  = true;
        }
        $stmt_gh->close();
    }

    // If not found create and and fetch it again
    if(!$found) {
        //Get IP from connecting party for the server perspective. Do not trust the user information
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($stmt_ch = $mysqli->prepare("CALL CreateHuurder(?, ?, ?, ?, ?, ?, ?, ?)")){
            $stmt_ch->bind_param("ssssssss", $naam, $contact, $mail, $telefoon, $postcode, $plaats, $adres, $ip);
            $stmt_ch->execute();
            $stmt_ch->close();

            //$hid = $mysqli->insert_id; Apparantly does not work
            //and hence we get it with a the reexecution of the get query
            if($stmt_gh = $mysqli->prepare("CALL GetHuurder(?, ?, ?, ?, ?, ?, ?)")){
                $stmt_gh->bind_param("sssssss", $naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats);
                $stmt_gh->execute();
                $stmt_gh->bind_result($id);
                while ($$stmt_gh->fetch()) {
                    $huurderid = $id;
                }
                $stmt_gh->close();
            }
        }
    }
    $mysqli->close();

    return $huurderid;
}

/**
 * Gets the Reservering from the table, if not present it creates it (which is more likely). 
 * 
 * @param $area The description of the resevering
 * @param $startSTR The string representation of the starting date of the Reservering
 * @param $endSTR The string representation of the ending date of the Resevering
 * @param $aantalPers THe number of person for which the Resevering is made
 * @return The id of the Reservering in the database, returns -1 if somehting goes wrong. 
 */
function getReservering($area, $startSTR, $endSTR, $aantalPers){
    $reserveringid = -1;

    $mysqli = databaseMYSQLi();
    if($stmt_ir = $mysqli->prepare("CALL InsertReservering(?, ?, ?, ?)")){
        $stmt_ir->bind_param("ssss", $area, $startSTR, $endSTR, $aantalPers);
        $stmt_ir->execute();
        $stmt_ir->close();

        //$rid = $mysqli->insert_id; Apparantly does not work
        //and hence we get it with a new query
        if($stmt_gr = $mysqli->prepare("CALL GetReservering(?, ?, ?, ?)")){
            $stmt_gr->bind_param("ssss", $area, $startSTR, $endSTR, $aantalPers);
            $stmt_gr->execute();
            $stmt_gr->bind_result($id);
            while ($stmt_gr->fetch()) {
                $reserveringid = $id;
            }
            $stmt_gr->close();
        }
    }

    $mysqli->close();

    return $reserveringid;
}

/**
 * Creates the Verhuring of the Resevering for the Huurder 
 * 
 * @param $hid The id of the Huurder in the database
 * @param $rid The id of the Reservering in the database
 * @return void
 */
function createVerhuring($hid, $rid, $groepcode){
    $mysqli = databaseMYSQLi();
    if($stmt_iv = $mysqli->prepare("CALL InsertVerhuring(?, ?, ?)")){
        $stmt_iv->bind_param("iis", $hid, $rid,  $groepcode);
        $stmt_iv->execute();
        $stmt_iv->close();
    }
    $mysqli->close();
}

/**
 * Gets the Confirm hash for the Verhuring that is needed to send to the Huurder
 * 
 * @param $hid The id of the Huurder in the database
 * @param $rid The id of the Reservering in the database
 * @return The hash for the Verhuring
 */
function getConfirm($hid, $rid){
    $hashEmail = "error";
    $mysqli = databaseMYSQLi();
    if($stmt_gc = $mysqli->prepare("CALL GetConfirm(?, ?)")){
        $stmt_gc->bind_param("ii", $hid, $rid);
        $stmt_gc->execute();
        $stmt_gc->bind_result($hash);
        while ($stmt_gc->fetch()) {
            $hashEmail = $hash;
        }
        $stmt_gc->close();
    }
    $mysqli->close();

    return $hashEmail;
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
            <a href='link" . $hashEmail . "'>link" . $hashEmail . "</a>\r\n\r\n
            Met vriendelijke groeten,\r\n
            Verhuurder Scouting Veghel";
    $headers = "From: Verhuur Scouting Veghel <" . $svmail . ">\r\n";
    $headers .= "Reply-To: " . $svmail;
    mail($toMail, $subject, $message, $headers);
}

/**
 * Converts the month name to month number
 *
 * @param $monthName The string representation of the Month (Duthc names)
 * @return The number of the month from 1 to 12
 */
function getMonthNumber($monthName){
    switch ($monthName) {
        case "Januari":
            return 1;
        case "Februari":
            return 2;
        case "Maart":
            return 3;
        case "April":
            return 4;
        case "Mei":
            return 5;
        case "Juni":
            return 6;
        case "July":
            return 7;
        case "Augustus":
            return 8;
        case "September":
            return 9;
        case "Oktober":
            return 10;
        case "November":
            return 11;
        case "December":
            return 12;
        default:
            return -1;
    }
}

/**
 * Check whether the date that is given is valid (basic checks)
 * 
 * @param $year The year number
 * @param $month The month number
 * @param $day The day number of the month
 * @param $hour The hour of the day (24h)
 * @param $minute The minute of the hour
 * @return true of valid, false otherwise
 */
function validDate($year, $month, $day, $hour, $minute){
    $y = filter_var($year, FILTER_VALIDATE_INT);
    $m = filter_var($month, FILTER_VALIDATE_INT);
    $d = filter_var($day, FILTER_VALIDATE_INT);
    $h = filter_var($hour, FILTER_VALIDATE_INT);
    $min = filter_var($minute, FILTER_VALIDATE_INT);

    if($d <= 31 && $d > 0 && $m <= 12 && $m > 0 && $y > 0 && $h <= 24 && $h >= 0 && $m <= 60 && $m >= 0) {
        return true;
    } else {
        return false;
    }
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