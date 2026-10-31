<?php
require_once __DIR__ . '/db_layer.php';
require_once __DIR__ . '/date_layer.php';

function handleReserveringVerificationRequest(array $input): array
{
	$d1 = filter_var($input['d1'] ?? null, FILTER_VALIDATE_INT);
	$m1 = filter_var(getMonthNumber($input['m1'] ?? ''), FILTER_VALIDATE_INT);
	$y1 = filter_var($input['y1'] ?? null, FILTER_VALIDATE_INT);
	$h1 = filter_var(isset($input['h1']) && $input['h1'] === '00' ? 0 : ($input['h1'] ?? null), FILTER_VALIDATE_INT);
	$min1 = filter_var(isset($input['min1']) && $input['min1'] === '00' ? 0 : ($input['min1'] ?? null), FILTER_VALIDATE_INT);

	$d2 = filter_var($input['d2'] ?? null, FILTER_VALIDATE_INT);
	$m2 = filter_var(getMonthNumber($input['m2'] ?? ''), FILTER_VALIDATE_INT);
	$y2 = filter_var($input['y2'] ?? null, FILTER_VALIDATE_INT);
	$h2 = filter_var(isset($input['h2']) && $input['h2'] === '00' ? 0 : ($input['h2'] ?? null), FILTER_VALIDATE_INT);
	$min2 = filter_var(isset($input['min2']) && $input['min2'] === '00' ? 0 : ($input['min2'] ?? null), FILTER_VALIDATE_INT);

	if (validDate($y1, $m1, $d1, $h1, $min1) && validDate($y2, $m2, $d2, $h2, $min2)) {
		$min1 = ($min1 < 10 ? '0' . $min1 : (string) $min1);
		$min2 = ($min2 < 10 ? '0' . $min2 : (string) $min2);

		return [200, getReservationsDates($d1, $m1, $y1, $h1, $min1, $d2, $m2, $y2, $h2, $min2)];
	}

	return [400, 'Geen geldige data opgegeven.'];
}

if (php_sapi_name() !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
	[$status, $payload] = handleReserveringVerificationRequest($_POST);

	if ($status === 200) {
		header('Content-Type: application/json');
		http_response_code(200);
		echo json_encode($payload);
	} else {
		header('Content-Type: text/plain; charset=utf-8');
		http_response_code(400);
		echo $payload;
	}
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
    $startStr = sprintf('%04d-%02d-%02d %02d:%02d:00', $y1, $m1, $d1, $h1, $min1);
    $endStr = sprintf('%04d-%02d-%02d %02d:%02d:59', $y2, $m2, $d2, $h2, $min2);
    
    $start = DateTime::createFromFormat('Y-m-d H:i:s', $startStr);
    $end = DateTime::createFromFormat('Y-m-d H:i:s', $endStr);
    
    if ($start === false || $end === false) {
        return [];
    }
    
    return getReservations($start, $end);
}

?>
