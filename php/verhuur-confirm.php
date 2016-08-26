<?php 

require_once("db_layer.php");
require_once("mail_layer.php");
//require_once("debug_layer.php");

session_start();
date_default_timezone_set('Europe/Paris');

if(isset($_GET["key"])) {
	$key = trim(strip_tags($_GET["key"]), " \n");

	$rid = getVerhuringFromConfirm($key);

	if ($rid === -1) {
		confirmDecline();
	} else {
		// First check if it can actually be updated
		$updatable = isReserveringConfirmable($rid);

		//If so, update DB to confirmed and send email
		if($updatable) {
			reserveringConfirmed($rid);
			sendConfirmEmailEllen();
			confirmAccept();
		} else {
			confirmAlready();
		}
		
	}
}


/**
 * Shows the message that indicates the Verhuring is confirmed
 */
function confirmAccept() {
	echo "Uw reservering is bevestigd, u hoort zo speodig mogelijk van ons.";
	header('HTTP/1.1 200 Ok');
    exit;
}

/**
 * Shows the message that indicates the Verhuring is already confirmed
 */
function confirmAlready() {
	echo "Uw reservering is reeds bevestigd, u hoort zo speodig mogelijk van ons.";
	header('HTTP/1.1 200 Ok');
    exit;
}

/**
 * Shows the messages that indicates that the verhuring could not be found
 */
function confirmDecline() {
	echo "Uw reservering kon niet bevestigd worden, controleer of de link wel goed is.";
	header('HTTP/1.1 200 Ok');
    exit;
}
?>