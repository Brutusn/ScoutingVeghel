<?php

require_once("settings.php");

session_start();
date_default_timezone_set(TIME_ZONE);

if (isset($_POST["name"]) && isset($_POST["mailadr"]) && isset($_POST["whoTo"]) && isset($_POST["tArea"])) {
    $naam = trim(strip_tags($_POST["name"]), " \n");
    $mail = trim(strip_tags($_POST["mailadr"]), " \n");
    $who = trim(strip_tags($_POST["whoTo"]), " \n");
    $area = trim(strip_tags($_POST["tArea"]), " \n");
    $toMail = "";

    if ($naam != "" && $who != "") {
        $headers = "From: $naam <" . $mail . ">\r\n";

        switch ($who) {
            case "gebe":
                $toMail = MAIL_ADDRESS_GEBE;
                break;
            case "verhuur":
                $toMail = MAIL_ADDRESS_VERHUUR;
                break;
            case "sponsor":
                $toMail = MAIL_ADDRESS_BESTUUR;
                break;
            case "site":
                $toMail = MAIL_ADDRESS_WEBSITE;
                break;
            default:
                $toMail = "";
                break;
        }
        $headers .= "Reply-To: " . $mail;
    }

    $subject = "[SV-Contact]: Vraag/opmerking van: \"$naam\"";
    $message = htmlentities($area);

    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        echo "Fout: E-mailadres ongeldig.";
        header('HTTP/1.1 400 Bad Request');
        exit;
    }
    else if (!mail($toMail, $subject, $message, $headers)) {
        echo "Fout: Verzenden e-mail mislukt.";
        header('HTTP/1.1 400 Bad Request');
        exit;
    } else {
        echo 'Hallo ' . $naam . ', je vraag of opmerking is verstuurd. Er zal zo spoedig mogelijk een antwoord gegeven worden.';
        header('HTTP/1.1 200 Ok');
        exit;
    }
}
?>
