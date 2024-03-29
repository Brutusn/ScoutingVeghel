<?php

echo (extension_loaded('openssl')?'SSL loaded':'SSL not loaded')."<br>";

require_once("../php/PhpMailerProxy.php");

//check if all main parameters are present in the request
if (
    isset($_POST["toMail"])
    && isset($_POST["subject"])
    && isset($_POST["message"])
) {
    echo "Stripping input and sending mail <br>";

    //Store all variables and trim them
    $toMail = trim(strip_tags($_POST["toMail"]), " \n");
    $subject = trim(strip_tags($_POST["subject"]), " \n");
    $message = trim(strip_tags($_POST["message"]), " \n");
    $replyToMail = trim(strip_tags($_POST["replyToMail"]), " \n");

    echo "To: $toMail <br>";
    echo "Subject: $subject <br>";
    echo "Message: $message <br>";
    echo "Reply To: $replyToMail <br>";

    $result = array(false, "nothing done");
    if (!empty($replyToMail)) {
        $result = sendMail($toMail, $subject, $message, $replyToMail);
    } else {
        $result = sendMail($toMail, $subject, $message);
    }

    echo "Result: ".($result[0]?"True":"False")." <br>";
    echo "Result Message: $result[1] <br>";
} else {
    echo "Not all was set";
}
?>
