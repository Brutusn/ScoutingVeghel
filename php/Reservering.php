<?php
require_once("db_layer.php");
require_once("date_layer.php");

$d = $_POST['d'];
$m = $_POST['m'];
$y = $_POST['y'];

$d = filter_var($d, FILTER_VALIDATE_INT);
$m = filter_var($m, FILTER_VALIDATE_INT);
$y = filter_var($y, FILTER_VALIDATE_INT);

//TODO Add the possibility to specify an end date. If not set use the behaviour specified below and if set check for that specific interval
if(validDate($y, $m, $d, 0, 0)) {
	header('HTTP/1.1 200 OK');
	echo json_encode(getReservationsNextDays($d, $m, $y));
    exit;
} else {
	header('HTTP/1.1 400 Bad Request');
	echo "Geen geldige datum opgegeven.";
    exit;
}

/**
 * Returns all reservations that take place during the given month and year.
 * A reservation is considered to take place in the period if it has an overlap
 * (the union of the reservation and the month is not empty)
 *
 * @param $m The number of the month
 * @param $y The year
 * @pre $m and $y are integers
 * @return array An associated array ('begin', 'end', 'isSV') with the reservations for this month
 */
function getReservationsMonth($m, $y)
{
    //if the month is 12, we know that the next month is 01 and in the next year
    $y2 = ($m == 12) ? $y + 1 : $y;
    //month 13 does not exist so count modulo
    $m2 = ($m == 12 ) ? 1 : ($m + 1) ;

    return getReservations(DateTime::createFromFormat('Y-m-d H:i:s', "" . $y . "-" . $m . "-01 00:00:00"),
        DateTime::createFromFormat('Y-m-d H:i:s', "" . $y2 . "-" . $m2 . "-01 00:00:00"));
}

/**
 * Returns all reservations for the period starting at the current day ($d) and ending at that day next month
 * A reservation is considered to take place in the period if it has an overlap
 * (the union of the reservation and the month is not empty)
 *
 * @param $d The current day number
 * @param $m The number of the month
 * @param $y The year
 * @pre $m and $y are integers
 * @return array An associated array ('begin', 'end', 'isSV') with the reservations for this month
 */
function getReservationsNextDays($d, $m, $y)
{
    //if the month is 12, we know that the next month is 01 and in the next year
    $y2 = ($m == 12) ? $y + 1 : $y;
    //month 13 does not exist so count modulo
    $m2 = ($m == 12 ) ? 1 : ($m + 1) ;
		//catch the error in case the day does not exist in the new month
		$d2 = ($d > 28 && $m2 == 2) ? 28 : ($d > 30 && ($m2 == 4 || $m2 == 6 || $m2 == 9 || $m2 == 11) ? 30 : $d);

    return getReservations(DateTime::createFromFormat('Y-m-d H:i:s', "" . $y . "-" . $m . "-" . $d . " 00:00:00"),
        DateTime::createFromFormat('Y-m-d H:i:s', "" . $y2 . "-" . $m2 . "-" . $d2 . " 00:00:00"));
}

?>
