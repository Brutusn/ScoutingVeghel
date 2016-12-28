<?php
require_once("db_layer.php");
require_once("date_layer.php");

$m = $_POST['m'];
$y = $_POST['y'];

$m = filter_var($m, FILTER_VALIDATE_INT);
$y = filter_var($y, FILTER_VALIDATE_INT);

//TODO Add the possibility to specify an end date. If not set use the behaviour specified below and if set check for that specific interval
if(validDate($y, $m, 1, 0, 0)) {
	header('HTTP/1.1 200 OK');
	echo json_encode(getReservationsMonth($m, $y));
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
?>
