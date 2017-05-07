<?php

require_once('verhuur_settings.php');

/**
 * Computes the difference between two dates in hours. Note that this only considers days and hours and does not scale for months and years
 */
function getDifferenceInHours($begin, $end){
	$begin_date = DateTime::createFromFormat('Y-m-d H:i:s', $begin);
	$end_date = DateTime::createFromFormat('Y-m-d H:i:s', $end);

	$difference = $begin_date->diff($end_date);

	//Fetch the difference of the hours here
	$hours = $difference->d * 24 + $difference->h;
	//in case there is a difference in minutes, round up
	$hours += ($difference->i > 0 ? 1 : 0);

	return $hours;
}

/**
 * Computes the difference between two dates in days. Note that this only considers days and does not scale for months and years.
 */
function getDifferenceInDays($begin, $end){
	$begin_date = DateTime::createFromFormat('Y-m-d H:i:s', $begin);
	$end_date = DateTime::createFromFormat('Y-m-d H:i:s', $end);

	$difference = $begin_date->diff($end_date);

	//Fetch the difference of the days here
	$days = $difference->d;

	return $days;
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
