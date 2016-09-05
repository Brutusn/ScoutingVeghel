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
			//TODO send email with huurcontract and voorwaarden
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
	redirect("Uw reservering is bevestigd, u hoort zo spoedig mogelijk van ons.");
}

/**
 * Shows the message that indicates the Verhuring is already confirmed
 */
function confirmAlready() {
	redirect("Uw reservering is reeds bevestigd, u hoort zo spoedig mogelijk van ons.");
}

/**
 * Shows the messages that indicates that the verhuring could not be found
 */
function confirmDecline() {
	redirect("Uw reservering kon niet bevestigd worden, controleer of de link wel goed is.");
}

function redirect($msg) {
	echo ('<!DOCTYPE html>
			<html>
				<head>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

					<meta name="no-robots" content="all">
					<meta name="viewport" content="width=device-width, minimum-scale=1.0">

					<title>Bevestiging verhuur | Scouting Veghel</title>
					<link href="../css/style.min.css" type="text/css" rel="stylesheet">

					<link rel="apple-touch-icon" sizes="57x57" href="../images/favicons/apple-touch-icon-57x57.png">
					<link rel="apple-touch-icon" sizes="60x60" href="../images/favicons/apple-touch-icon-60x60.png">
					<link rel="apple-touch-icon" sizes="72x72" href="../images/favicons/apple-touch-icon-72x72.png">
					<link rel="apple-touch-icon" sizes="76x76" href="../images/favicons/apple-touch-icon-76x76.png">
					<link rel="apple-touch-icon" sizes="114x114" href="../images/favicons/apple-touch-icon-114x114.png">
					<link rel="apple-touch-icon" sizes="120x120" href="../images/favicons/apple-touch-icon-120x120.png">
					<link rel="apple-touch-icon" sizes="144x144" href="../images/favicons/apple-touch-icon-144x144.png">
					<link rel="apple-touch-icon" sizes="152x152" href="../images/favicons/apple-touch-icon-152x152.png">
					<link rel="apple-touch-icon" sizes="180x180" href="../images/favicons/apple-touch-icon-180x180.png">

					<link rel="icon" type="image/png" href="../images/favicons/favicon-32x32.png" sizes="32x32">
					<link rel="icon" type="image/png" href="../images/favicons/android-chrome-192x192.png" sizes="192x192">
					<link rel="icon" type="image/png" href="../images/favicons/favicon-96x96.png" sizes="96x96">
					<link rel="icon" type="image/png" href="../images/favicons/favicon-16x16.png" sizes="16x16">
					<link rel="manifest" href="../images/favicons/manifest.json">
					<link rel="mask-icon" href="../images/favicons/safari-pinned-tab.svg" color="#5bbad5">
					<link rel="shortcut icon" href="../images/favicons/favicon.ico">

					<meta name="msapplication-TileColor" content="#da532c">
					<meta name="msapplication-TileImage" content="../images/favicons/mstile-144x144.png">
					<meta name="msapplication-config" content="../images/favicons/browserconfig.xml">

					<meta name="theme-color" content="#dddac4">

					<meta http-equiv="refresh" content="10; url=http://nieuw.scoutingveghel.nl/" />
				</head>
				<body>
				<div class="activiteiten fixed-background" style="padding: 15vh 0 15vh 0; height: 100vh; color: #202020;">
					<div class="section verhuur">
					<span class="size-limit">
						<h2>Status bevestiging genomen optie:</h2>
						<p><strong>' . $msg . '</strong></p>
						<a href="http://nieuw.scoutingveghel.nl" style="color: inherit; font-size: 75%;">Klik hier als u niet teruggestuurd wordt naar de begin pagina.</a>
					</span>
					</div>
				</div>
				</body>
			</html>');
	header('HTTP/1.1 200 Ok');
    exit;
}
?>
