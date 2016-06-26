<?php

require_once("DB2.php");

session_start();
date_default_timezone_set('Europe/Paris');

error_reporting(1);

//check if all fields are filled in
//TODO still need to add the dateFrom and the dateTo
if (isset($_POST["name"]) && isset($_POST["contact"]) && isset($_POST["mailadr"]) && isset($_POST["telefoon"]) 
    && isset($_POST["adres"]) && isset($_POST["postcode"]) && isset($_POST["plaats"]) 
    && isset($_POST["aantalPers"]) && isset($_POST["tArea"]) && isset($_POST["groepsCode"])) {

    $naam = trim(strip_tags($_POST["name"]), " \n");
    $contact = trim(strip_tags($_POST["contact"]), " \n");
    $mail = trim(strip_tags($_POST["mailadr"]), " \n");
    $telefoon = trim(strip_tags($_POST["telefoon"]), " \n");
    $adres = trim(strip_tags($_POST["adres"]), " \n");
    $postcode = trim(strip_tags($_POST["postcode"]), " \n");
    $plaats = trim(strip_tags($_POST["plaats"]), " \n");
    $aantalPers = trim(strip_tags($_POST["aantalPers"]), " \n");
    $area = trim(strip_tags($_POST["tArea"]), " \n");
    $groepscode = trim(strip_tags($_POST["groepsCode"]), " \n");

    //First check if either the groepscode is filled in (so made by one of our own groups) or if it is filled in by an external party
    if ($groepsCode != "" && $area != "") {
        //Get information based on the group code and process the request

    }
    else if ($naam != "" && $contact != "" && filter_var($mail, FILTER_VALIDATE_EMAIL) && $telefoon != "" && $adres != "" && $postcode != "" && $plaats != "" && $aantalPers != "" && $area) {
        //Filled in by external party (not own group)
        
        //First check if the party already exists
        $mysqli = databaseMYSQLi();
        $stmt = $mysqli->prepare("CALL GetHuurder(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $naam, $contact, $mail, $telefoon, $postcode, $plaats, $adres);
        $stmt->execute();
        if($stmt->num_rows != 0) {
            //Since we always check if all fields are the same, we never get two Huurders with the same information
            $stmt->bind_result($hid);
        } else {
            //Get IP from connecting party for the server perspective. Do not trust the user information
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $mysqli->prepare("CALL CreateHuurder(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats, $ip);
            $stmt->execute();
            $hid = $stmnt->mysql_insert_id();
        }

        //Then create the reservation and do the real processing of the request
        $startSTR = $start->format("Y-m-d H:i:s");
        $endSTR = $end->format("Y-m-d H:i:s");
        //First create the Reservering
        $stmt = $mysqli->prepare("CALL InsertReservering(?, ?, ?, ?)");
        $stmt->bind_param("ssss", $area, $start, $end, $aantalPers);
        $stmt->execute();
        $rid = $stmnt->mysql_insert_id();

        //Then create the link between de verhuurder and the reservering (the actual verhuring)
        $today = time();
        $todaySTR = $today->format("Y-m-d H:i:s");

        $stmt = $mysqli->prepare("CALL InsertVerhuring(?, ?, ?)");
        $stmt->bind_param("iis", $hid, $rid, 'NULL');
        $stmt->execute();
        $stmt = $mysqli->prepare("CALL GetConfirm(?, ?)");
        $stmt->bind_param("ii", $hid, $rid);
        $stmt->execute();
        $stmt->bind_result($hash);

        //Then send confirmation email to verhuurder with confirm string
        $toMail = $mail;
        $svmail = "verhuur@scoutingveghel.nl";
        $subject = "Aanvraag huren blokhut Scouting Veghel";
        //TODO improve messages
        $message = htmlentities("Beste \"$naam\",\r\n\r\n
            Hierbij de email om uw reservering te bevestigen. U bevestigt uw resevrering door op de onderstaande link te klikken: \r\n\r\n
            <a href='link\"$hash\"'>link\"$hash\"</a>\r\n\r\n
            Met vriendelijke groeten,\r\n
            Verhuurder Scouting Veghel
            ");
        echo $message;
        $headers = "From: Verhuur Scouting Veghel <" . $svmail . ">\r\n";
        $headers .= "Reply-To: " . $svmail;
        mail($toMail, $subject, $message, $headers);
    }
        
}
?>