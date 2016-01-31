<?php

require_once("DB2.php");

session_start();
date_default_timezone_set('Europe/Paris');

error_reporting(1);

//check if all fields are fille din
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
    else if ($naam != "" && $contact != "" && $mail != "" && $telefoon != "" && $adres != "" && $postcode != "" && $plaats != "" && $aantalPers != "" && $area) {
        //Filled in by external party
        
        //First cehck if the party already exists

        $mysqli = databaseMYSQLi();
        $stmt = $mysqli->prepare("CALL GetHuurder(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $naam, $contact, $mail, $telefoon, $adres, $postcode, $plaats);
        $stmt->execute();
        $stmt->bind_result($hid);

        //If the party does not exists, create it
        if ($hid->fetchColumn())



        //Then create the reservation and do the real processing of the request
        $startSTR = $start->format("Y-m-d H:i:s");
        $endSTR = $end->format("Y-m-d H:i:s");
        //First create the Reservering

        //Then create the link between de verhuurder and the reservering (the actual verhuring)
        $today = time();
        $todaySTR = $today->format("Y-m-d H:i:s");

        //Then send confirmation email to verhuurder with confirm string

    }
        
}
?>