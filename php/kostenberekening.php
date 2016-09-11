<?php

require_once('verhuur_settings.php');
// Old db script is included here for the internally used functionality.
//TODO refactor to not use this db script and switch to mysqli
require_once('database.php');

if(!isset($database)){
	$database = new database();
}

function getDifferenceInHours($begin, $eind){
	global $database;
	$q = "SELECT TIMEDIFF('".$eind."','".$begin."') 'd'";
	$r = $database->query($q);

	$hours = mysql_fetch_assoc($r);
	$hours = explode(':',$hours['d'],3);

	$difference = $hours[0] + ($hours[1] == 0 ? 0 : 1);
	return $difference;
}

function getDifferenceInDays($begin, $eind){
	global $database;
	$q = "SELECT DATEDIFF('".$eind."','".$begin."') 'd'";
	$r = $database->query($q);

	$days = mysql_fetch_assoc($r);
	return $days['d'];
}

function getKostenByDate($begin, $eind, $personen){
	$d = getDifferenceInDays($begin, $eind);
	$h = getDifferenceInHours($begin, $eind);
	return getKosten($d,$h, $personen);
}

function getBorg($d){
	switch($d){
		case 0: 	return BEDRAG_BORG_DAGDEEL;
					break;
		case 1:		return BEDRAG_BORG_NACHT;
					break;
		default:	return BEDRAG_BORG_LANGER;
	}
}

function getKosten($d, $h, $p){
  if ($h > 8 && $d == 0)
  {
    $d = 1;
    $h = 0;
  }
  switch ($d){
    case 0:  return $h * KOSTEN_PER_UUR + getBorg($d);
             break;
    default: return ($d * $p * KOSTEN_PPPN) + getBorg($d);
  }
}
?>
