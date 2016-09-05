<?php

require_once("DB2.php");

//==========Huurder========================================================================================

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
    $mysqli->close();

    // If not found create and and fetch it again
    if(!$found) {
        //Get IP from connecting party for the server perspective. Do not trust the user information
        $ip = $_SERVER['REMOTE_ADDR'];
        $mysqli = databaseMYSQLi();
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
                while ($stmt_gh->fetch()) {
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
 * Get HuurderID based on teh specified groepscode
 *
 * @param $code The groepscode that is given to the SV groepen
 * @return The HuurderID that corresponds to a valid groepscode, -1 otherwise
 */
function getHuurderIDFromCode($code) {
    $hid = -1;
    $mysqli = databaseMYSQLi();
    if($stmt_ghc = $mysqli->prepare("CALL HuurderIDGroepscode(?)")){
        $stmt_ghc->bind_param("s", $code);
        $stmt_ghc->execute();
        $stmt_ghc->bind_result($huurder_id);
            while ($stmt_ghc->fetch()) {
                $hid = $huurder_id;
            }
        $stmt_ghc->close();
    }
    $mysqli->close();

    return $hid;
}

/**
 * Get the Name and Email form a Huurder based on its ID
 *
 * @param $hid The HuurderID in the database
 * @return An array with [name, email] as they are stored in the database, or ["", ""] if it was an invalid HuurderID
 */
function getInfoFromHid($hid){
    $info = ["", ""];
    if($hid == -1) {
        return $info;
    }

    $mysqli = databaseMYSQLi();
    if($stmt_ghi = $mysqli->prepare("CALL GetHuurderFromID(?)")){
        $stmt_ghi->bind_param("i", $hid);
        $stmt_ghi->execute();
        $stmt_ghi->bind_result($naam, $mail);
            while ($stmt_ghi->fetch()) {
                $info[0] = $naam;
                $info[1] = $mail;
            }
        $stmt_ghi->close();
    }
    $mysqli->close();

    return $info;
}



//==========Reservering====================================================================================

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
 * Returns all reservations that take place during the period from $start to $end.
 * A reservation is considered to take place in the period if it has an overlap
 * (the union of the reservation and the interval is not empty)
 *
 * @param DateTime $start The start date and time of the search interval
 * @param DateTime $end The end date and time of the search interval
 * @return array An associated array ('begin', 'end', 'isSV') with the reservations for this month
 */
function getReservations(DateTime $start, DateTime $end)
{
    $startSTR = $start->format("Y-m-d H:i:s");
    $endSTR = $end->format("Y-m-d H:i:s");

    $array = [];

    $mysqli = databaseMYSQLi();
    if($stmt = $mysqli->prepare("CALL GetReservations(?, ?)")){
	    $stmt->bind_param("ss", $startSTR, $endSTR);
	    $stmt->execute();
	    $stmt->bind_result($begindate, $enddate, $groep);

	    while ($stmt->fetch()) {
	        $ar = [];//array('dayFrom', 'dayTo', 'bySV');
	        $begin = new DateTime($begindate);
	        $end = new DateTime($enddate);
	        $ar['dayFrom'] = $begin->format("Y-m-d");
	        $ar['dayTo'] = $end->format("Y-m-d");
	        $ar['bySV'] = ($groep == NULL) ? False : True;
	        $array[] = $ar;
	    }
	    $mysqli->close();
	}

    return $array;
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
 * Checks whether there is already a reservation durign the specified time frame
 *
 * @param $startSTR The start time of the specified time frame
 * @param $endSTR The end time fo the specified time frame
 * @return True if there is a reservation in that time frame and false otherwise
 */
function alreadyReserved(DateTime $start, DateTime $end){
  $reservations = getReservations($start, $end);
  if (count($reservations) == 0){
    return true;
  } else {
    return false;
  }
}






//==========Verhuring======================================================================================

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
 * Creates the Verhuring of the Resevering for the Huurder
 *
 * @param $hid The id of the Huurder in the database
 * @param $rid The id of the Reservering in the database
 * @param $groepcode The groepscode of a reservering of a group of SV
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

?>
