<?php
require_once __DIR__ . '/db_layer.php';
require_once __DIR__ . '/date_layer.php';

function handleReserveringRequest(array $input): array
{
  $d = filter_var($input['d'] ?? null, FILTER_VALIDATE_INT);
  $m = filter_var($input['m'] ?? null, FILTER_VALIDATE_INT);
  $y = filter_var($input['y'] ?? null, FILTER_VALIDATE_INT);

  if (validDate($y, $m, $d, 0, 0)) {
    return [200, getReservationsNextDays($d, $m, $y)];
  }

  return [400, 'Geen geldige datum opgegeven.'];
}

if (php_sapi_name() !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
  [$status, $payload] = handleReserveringRequest($_POST);

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
        DateTime::createFromFormat('Y-m-d H:i:s', "" . $y2 . "-" . $m2 . "-" . $d2 . " 23:59:59"));
}

?>
