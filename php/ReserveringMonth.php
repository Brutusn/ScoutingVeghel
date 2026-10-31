<?php
require_once __DIR__ . '/db_layer.php';
require_once __DIR__ . '/date_layer.php';

function handleReserveringMonthRequest(array $input): array
{
    $m = filter_var($input['m'] ?? null, FILTER_VALIDATE_INT);
    $y = filter_var($input['y'] ?? null, FILTER_VALIDATE_INT);

    if (validDate($y, $m, 1, 0, 0)) {
        return [200, getReservationsMonth($m, $y)];
    }

    return [400, 'Geen geldige datum opgegeven.'];
}

if (php_sapi_name() !== 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    [$status, $payload] = handleReserveringMonthRequest($_POST);

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
