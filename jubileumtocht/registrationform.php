<?php

require_once("db_layer.php");
require_once("mail_layer.php");

session_start();
date_default_timezone_set('Europe/Paris');

//check if all parameters are present in the request
if (
    isset($_POST["name"]) && isset($_POST["mail"]) && isset($_POST["slot"]) && isset($_POST["walkers"])
) {

    //Store all variables and trim them
    $name = trim(strip_tags($_POST["name"]), " \n");
    $mail = trim(strip_tags($_POST["mail"]), " \n");
    $slotid = trim(strip_tags($_POST["slot"]), " \n");
    $amountOfWalkers = trim(strip_tags($_POST["walkers"]), " \n");

    if ($name == "") {
        incompleteData("Naam");
    }
    if ($mail == "" || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        incompleteData("E-mail");
    }
    if ($amountOfWalkers == "") {
        incompleteData("Aantal deelnemers");
    }
    if ($slotid == "" || $slotid < 0 || $slotid > 23) {
        incompleteData("Tijdslot");
    }

    $availableWalkers = getAmountOfAvailableWalkers($slotid);

    if ($amountOfWalkers > $availableWalkers) {
        tooManyWalkers($amountOfWalkers, $availableWalkers);
    }

    $participantId = createParticipant($name, $mail);
    if ($participantId < 0) {
        errorDatabase("Aanmaken van de deelnemer");
    }

    createRegistration($participantId, $slotid, $amountOfWalkers);

    $slotData = getSlotData($slotid);
    $time = $slotData['timeslot'];
    $distance = $slotData['distance'];

    sendConfirmEmail($mail, $name, $time, $distance, $amountOfWalkers);
    successfulRegistration();
} else { //one of the fields was not set in the POST request
    missingData();
}

function incompleteData($invalidData)
{
    echo "Niet alle velden zijn correct ingevuld. Specifiek de velden: $invalidData.";
    exit;
}

function missingData()
{
    echo "Niet alle velden zijn ingevuld.";
    exit;
}

function tooManyWalkers($walkers, $available)
{
    echo "De aanmelding is voor meer deelnemers (" . $walkers . ") dan er beschikbaar zijn (" . $available . ")";
    exit;
}

function errorDatabase($property)
{
    echo "Er is iets fout gegaan (" . $property . "), probeer het alsutblieft opnieuw. Als de fout zich blijft voordoen, neem dan contact op met de webmaster.";
    exit;
}

function successfulRegistration()
{
    echo "We hebben je aanmelding ontvangen.<br>
    Wat leuk dat je meeloopt met de 11-Spelentocht!<br>
    We hebben een bevestiging gestuurd naar je e-mailadres.<br>
    Zorg dat je je precies in het tijdvak aanmeld bij de startlocatie naast de silo's op de Noordkade.<br>
    LET OP: Kinderen die nog op de basisschool zitten kunnen alleen deelnemen onder begeleiding.";
    exit;
}
