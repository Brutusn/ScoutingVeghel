<?php
require_once("DB2.php");

session_start();
date_default_timezone_set('Europe/Paris');

error_reporting(1);

//check if all fields are filled in
//TODO still need to add the dateFrom and the dateTo
// aankomst-dag, maand, jaar, uur, minuut
// vertrek-...
//TODO make the names better for the inputs (not consitent with the actual site right now)
if (isset($_POST["name"]) && isset($_POST["contactperson"]) && isset($_POST["mailadr"]) && isset($_POST["phone"]) 
    && isset($_POST["adress"]) && isset($_POST["postcode"]) && isset($_POST["city"]) 
    && isset($_POST["people"]) && isset($_POST["tArea"]) && isset($_POST["groepcode"])
    && isset($_POST["aankomst-dag"]) && isset($_POST["aankomst-maand"]) && isset($_POST["aankomst-jaar"])
    && isset($_POST["aankomst-uur"]) && isset($_POST["aankomst-minuut"])
    && isset($_POST["vertrek-dag"]) && isset($_POST["vertrek-maand"]) && isset($_POST["vertrek-jaar"])
    && isset($_POST["vertrek-uur"]) && isset($_POST["vertrek-minuut"])
    ) {

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

    //TODO make sure the dates are ok and convert month names to month numbers
    $aankomstmaandNummer = 1; //TODO fix
    $vertrekmaandNummer = 1; //TODO fix
    if (false) {

    } else { //date are ok, continue processing

        //First check if either the groepscode is filled in (so made by one of our own groups) or if it is filled in by an external party
        if ($groepsCode != "" && $area != "") {
            //Get information based on the group code and process the request
            //echo "groepcode found </br>";#debug

        }
        else if ($naam != "" && $contact != "" && filter_var($mail, FILTER_VALIDATE_EMAIL) && $telefoon != "" && $adres != "" && $postcode != "" && $plaats != "" && $aantalPers != "" && $area) {
            //Filled in by external party (not own group)
            
            //First check if the party already exists
            $mysqli = databaseMYSQLi();
            $stmt = $mysqli->prepare("CALL GetHuurder(?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats);
            $stmt->execute();
            if($stmt->num_rows > 0) {
                //Since we always check if all fields are the same, we never get two Huurders with the same information
                $stmt->bind_result($hid);
                //TODO This does not seem to work
            } else {
                $mysqli->close();
                $mysqli = databaseMYSQLi();
                //Get IP from connecting party for the server perspective. Do not trust the user information
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt = $mysqli->prepare("CALL CreateHuurder(?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssss", $naam, $contact, $mail, $telefoon, $postcode, $plaats, $adres, $ip);
                $stmt->execute();
                $hid = mysqli_insert_id();
                $mysqli->close();
            }

            //Then create the reservation and do the real processing of the request
            $start = new DateTime();
            $start->setDate($aankomstjaar, $aankomstmaandNummer, $aankomstdag);
            $start->setTime($aankomstuur, $aankomstminuut);
            $startSTR = $start->format("Y-m-d H:i:s");
            $end = new DateTime();
            $end->setDate($vertrekjaar, $vertrekmaandNummer, $vertrekdag);
            $end->setTime($vertrekuur, $vertrekminuut);
            $endSTR = $end->format("Y-m-d H:i:s");

            //First create the Reservering
            $mysqli = databaseMYSQLi();
            $stmt = $mysqli->prepare("CALL InsertReservering(?, ?, ?, ?)");
            $stmt->bind_param("ssss", $area, $startSTR, $endSTR, $aantalPers);
            $stmt->execute();
            $rid = mysqli_insert_id();
            $mysqli->close();

            //Then create the link between de verhuurder and the reservering (the actual verhuring)
            $mysqli = databaseMYSQLi();
            $stmt = $mysqli->prepare("CALL InsertVerhuring(?, ?, ?)");
            $null = "NULL";
            var_dump($hid, $rid);#debyg // Both rid and hid are NULL, hence no insertion here
            $stmt->bind_param("iis", $hid, $rid, $null);
            $stmt->execute();
            $mysqli->close();

            $mysqli = databaseMYSQLi();
            $stmt = $mysqli->prepare("CALL GetConfirm(?, ?)");
            $stmt->bind_param("ii", $hid, $rid);
            $stmt->execute();
            if($stmt->num_rows > 0) {
                $stmt->bind_result($hash);
            }            
            $hashEmail = "error";
            while ($stmt->fetch()){
                $hashEmail = $hash;
                echo "fetching";
                var_dump($hashEmail);
                var_dump($hash);
            }
            $mysqli->close();

            //Then send confirmation email to verhuurder with confirm string
            $toMail = $mail;
            $svmail = "verhuur@scoutingveghel.nl";
            $subject = "Aanvraag huren blokhut Scouting Veghel";
            //TODO improve messages
            $message = htmlentities("Beste $naam,\r\n\r\n
                    Hierbij de email om uw reservering te bevestigen. U bevestigt uw resevrering door op de onderstaande link te klikken: \r\n\r\n")
                ."<a href='link".$hashEmail."'>link".$hashEmail."</a>".
                htmlentities("\r\n\r\n
                    Met vriendelijke groeten,\r\n
                    Verhuurder Scouting Veghel
                    ");
            $headers = "From: Verhuur Scouting Veghel <" . $svmail . ">\r\n";
            $headers .= "Reply-To: " . $svmail;
            mail($toMail, $subject, $message, $headers);
        }
            
    }
}
?>