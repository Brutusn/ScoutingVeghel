<?php
require_once("db_layer.php");
require_once("date_layer.php");

$d1 = $_POST['d1'];
$m1 = getMonthNumber($_POST['m1']);
$y1 = $_POST['y1'];
$h1 = ($_POST['h1'] == "00") ? 0 : $_POST['h1'];//catch the case were the input is 00 and is therefore interpereted as string
$min1 = ($_POST['min1'] == "00") ? 0 : $_POST['min1'];//catch the case were the input is 00 and is therefore interpereted as string
$d2 = $_POST['d2'];
$m2 = getMonthNumber($_POST['m2']);
$y2 = $_POST['y2'];
$h2 = ($_POST['h2'] == "00") ? 0 : $_POST['h2'];//catch the case were the input is 00 and is therefore interpereted as string
$min2 = ($_POST['min2'] == "00") ? 0 : $_POST['min2'];//catch the case were the input is 00 and is therefore interpereted as string

$d1 = filter_var($d1, FILTER_VALIDATE_INT);
$m1 = filter_var($m1, FILTER_VALIDATE_INT);
$y1 = filter_var($y1, FILTER_VALIDATE_INT);
$h1 = filter_var($h1, FILTER_VALIDATE_INT);
$min1 = filter_var($min1, FILTER_VALIDATE_INT);
$d2 = filter_var($d2, FILTER_VALIDATE_INT);
$m2 = filter_var($m2, FILTER_VALIDATE_INT);
$y2 = filter_var($y2, FILTER_VALIDATE_INT);
$h2 = filter_var($h2, FILTER_VALIDATE_INT);
$min2 = filter_var($min2, FILTER_VALIDATE_INT);

if(validDate($y1, $m1, $d1, $h1, $min1) && validDate($y2, $m2, $d2, $h2, $min2)) {
	//add the leading 0 for minutes that require it. PHP does not support minutes without elading 0 :'(
	$min1 = ($min1 < 10 ? '0'.$min1 : $min1);
	$min2 = ($min2 < 10 ? '0'.$min2 : $min2);
	header('HTTP/1.1 200 OK');
	echo json_encode(getReservationsDates($d1, $m1, $y1, $h1, $min1, $d2, $m2, $y2, $h2, $min2));
    exit;
} else {
	header('HTTP/1.1 400 Bad Request');
	echo "Geen geldige data opgegeven.";
    exit;
}

/**
 * Returns all reservations for the period starting at the date1 and ending at date2
 * A reservation is considered to take place in the period if it has an overlap
 * (the union of the reservation and the period is not empty)
 *
 * @param $d1 The current day number of date1
 * @param $m1 The number of the month of date1
 * @param $y1 The year of date1
 * @param $h1 The hour of date1
 * @param $min1 The minute of date1
 * @param $d2 The current day number of date2
 * @param $m2 The number of the month of date2
 * @param $y2 The year of date2
 * @param $h2 The hour of date2
 * @param $min2 The minute of date2
 * @pre all inputs are integers
 * @return array An associated array ('begin', 'end', 'isSV') with the reservations for this period
 */
function getReservationsDates($d1, $m1, $y1, $h1, $min1, $d2, $m2, $y2, $h2, $min2)
{
    return getReservations(DateTime::createFromFormat('Y-m-d H:i:s', "" . $y1 . "-" . $m1 . "-" . $d1 . " " . $h1 . ":" . $min1 . ":00"),
        DateTime::createFromFormat('Y-m-d H:i:s', "" . $y2 . "-" . $m2 . "-" . $d2 . " " . $h2 . ":" . $min2 . ":59"));
}

?>
