<?php

ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

require_once("DB2.php");

$d = $_GET['d'];
$m = $_GET['m'];
$y = $_GET['y'];

$d = filter_var($d, FILTER_VALIDATE_INT);
$m = filter_var($m, FILTER_VALIDATE_INT);
$y = filter_var($y, FILTER_VALIDATE_INT);

if($d <= 31 && $d > 0 && $m >= 1 && $m <= 12 && $y > 0) {
    //echo getReservationsMonth($m, $y);
	echo json_encode(getReservationsNextDays($d, $m, $y));
} else {
	echo "fout";
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

    $mysqli = databaseMYSQLi();
    $stmt = $mysqli->prepare("CALL GetReservations(?, ?)");
    $stmt->bind_param("ss", $startSTR, $endSTR);
    $stmt->execute();
    $stmt->bind_result($begindate, $enddate, $groep);

    $array = [];
    $i = 0;

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

    return $array;
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

    return getReservations(DateTime::createFromFormat("Y-m-d H:i:s", "" . $y . "-" . $m . "-01 00:00:00"),
        DateTime::createFromFormat("Y-m-d H:i:s", "" . $y2 . "-" . $m2 . "-01 00:00:00"));
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

    return getReservations(DateTime::createFromFormat("Y-m-d H:i:s", "" . $y . "-" . $m . "-" . $d . " 00:00:00"),
        DateTime::createFromFormat("Y-m-d H:i:s", "" . $y2 . "-" . $m2 . "-" . $d . " 00:00:00"));
}

?>