<?php
require_once("db_layer.php");
require_once("date_layer.php");
require_once("settings.php");

$d1 = $_POST['d1'];
$m1 = getMonthNumber($_POST['m1']);
$y1 = $_POST['y1'];
$d2 = $_POST['d2'];
$m2 = getMonthNumber($_POST['m2']);
$y2 = $_POST['y2'];

$d1 = filter_var($d1, FILTER_VALIDATE_INT);
$m1 = filter_var($m1, FILTER_VALIDATE_INT);
$y1 = filter_var($y1, FILTER_VALIDATE_INT);
$d2 = filter_var($d2, FILTER_VALIDATE_INT);
$m2 = filter_var($m2, FILTER_VALIDATE_INT);
$y2 = filter_var($y2, FILTER_VALIDATE_INT);

//TODO Add the possibility to specify an end date. If not set use the behaviour specified below and if set check for that specific interval
if(validDate($y1, $m1, $d1, 0, 0) && validDate($y2, $m2, $d2, 0, 0)) {
	header('HTTP/1.1 200 OK');
	echo json_encode(getReservationsDates($d1, $m1, $y1, $d2, $m2, $y2));
    exit;
} else {
	header('HTTP/1.1 400 Bad Request');
	echo "Geen geldige data opgegeven.";
    exit;
}

/**
 * Returns all reservations for the period starting at the date1 and ending at date2
 * A reservation is considered to take place in the period if it has an overlap
 * (the union of the reservation and the month is not empty)
 *
 * @param $d1 The current day number of date 1
 * @param $m1 The number of the month of date1
 * @param $y1 The year of date1
 * @pre all inputs are integers
 * @return array An associated array ('begin', 'end', 'isSV') with the reservations for this month
 */
function getReservationsDates($d1, $m1, $y1, $d2, $m2, $y2)
{
    return getReservations(DateTime::createFromFormat(DATE_TIME_FORMAT, "" . $y1 . "-" . $m1 . "-" . $d1 . " 00:00:00"),
        DateTime::createFromFormat(DATE_TIME_FORMAT, "" . $y2 . "-" . $m2 . "-" . $d2 . " 00:00:00"));
}

?>
