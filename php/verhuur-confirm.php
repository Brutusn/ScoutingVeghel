<?php 

require_once("DB2.php");

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
			sendConfirmEmail();
			confirmAccept();
		} else {
			confirmAlready();
		}
		
	}
}


/**
 * Get the id of the Reservering according to the confirm hash
 *
 * @param $key The confirmation key / hash
 * @return The id of the corresponding reservering or -1 if none present
 */
function getVerhuringFromConfirm($key) {
	$verhuringId = -1;
    $mysqli = databaseMYSQLi();
    if($stmt_gv = $mysqli->prepare("CALL GetVerhuringFromConfirm(?)")){
        $stmt_gv->bind_param("s", $key);
        $stmt_gv->execute();
        $stmt_gv->bind_result($hid, $rid);
        while ($stmt_gv->fetch()) {
            $reservering_id = $rid;
        }
        $stmt_gv->close();
    }
    $mysqli->close();

    return $reservering_id;
}

/**
 * Confirmes the Resevering
 *
 * @param $rid The id if the resevrering to be confirmed
 * @return void
 */
function reserveringConfirmed($rid) {
	$mysqli = databaseMYSQLi();
	if($stmnt_cr = $mysqli->prepare("CALL ConfirmReservering(?)")){
		$stmnt_cr->bind_param("i", $rid);
		$stmnt_cr->execute();
		$stmnt_cr->close();
	}
	$mysqli->close();
}

/**
 * Check whether the Reservering is confirmable
 *
 * @param $rid The id of the reservering
 * @return true when the reservering is confirmable and false otherwise (assuming that is was already confirmed earlier)
 */
function isReserveringConfirmable($rid) {
	$updatable = false;
	$mysqli = databaseMYSQLi();
	if($stmnt_rc = $mysqli->prepare("CALL ReserveringConfirmable(?)")){
		$stmnt_rc->bind_param("i", $rid);
		$stmnt_rc->execute();
		$stmnt_rc->bind_result($one);
        while ($stmnt_rc->fetch()) {
            $updatable = true;
        }
        $stmnt_rc->close();
    }
	$mysqli->close();

	return $updatable;
}

/**
 * Send the confirmation email to Ellen
 * 
 * @param $mail The mail address of the contact of the Huurder
 * @param $naam The name of the contact
 * @param $hashEmail The confirmation hash of the Verhuring
 * @return void
 */
function sendConfirmEmail(){
    $toMail = "website@scoutingveghel.nl";//verhuur@scoutingveghel.nl";
    $svmail = "website@scoutingveghel.nl";
    $subject = "Reservering blokhut Scouting Veghel Bevestigd";
    //TODO improve messages
    $message = "Beste Ellen,\r\n\r\n
            Er is weer een reservering voor de blokhut bevestigd. 
            Met vriendelijke groeten,\r\n
            Website Scouting Veghel";
    $headers = "From: Website Scouting Veghel <" . $svmail . ">\r\n";
    $headers .= "Reply-To: " . $svmail;
    mail($toMail, $subject, $message, $headers);
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