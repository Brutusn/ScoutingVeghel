<?php
function getMonthNumber($name) {
    if (!is_string($name)) {
        return -1;
    }
    $months = [
        'januari',
        'februari',
        'maart',
        'april',
        'mei',
        'juni',
        'juli',
        'augustus',
        'september',
        'oktober',
        'november',
        'december'
    ];
    $index = array_search(strtolower($name), $months, true);
    return $index === false ? -1 : $index + 1;
}
function validDate($year, $month, $day, $hour, $minute) {
    $parts = [$year, $month, $day, $hour, $minute];
    foreach ($parts as &$part) {
        if (!is_scalar($part) || !preg_match('/^\d+$/D', (string) $part)) {
            return false;
        }
        $part = (int) $part;
    }
    unset($part);
    [$year, $month, $day, $hour, $minute] = $parts;
    return $year >= 1 &&
        $year <= 9999 &&
        checkdate($month, $day, $year) &&
        $hour <= 23 &&
        $minute <= 59;
}
/** A missing or malformed POST value never reaches DateTime or SQL. */
function rentalPostDate($suffix = '', $lastSecond = false) {
    $day = $_POST['d' . $suffix] ?? null;
    $month = $_POST['m' . $suffix] ?? null;
    if ($suffix !== '') {
        $month = getMonthNumber($month);
    }
    $year = $_POST['y' . $suffix] ?? null;
    $hour = $suffix === '' ? 0 : $_POST['h' . $suffix] ?? null;
    $minute = $suffix === '' ? 0 : $_POST['min' . $suffix] ?? null;
    if (!validDate($year, $month, $day, $hour, $minute)) {
        return null;
    }
    return new DateTime(
        sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $year,
            $month,
            $day,
            $hour,
            $minute,
            $lastSecond ? 59 : 0
        )
    );
}
function rentalJsonResponse($value) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($value, JSON_THROW_ON_ERROR);
}
function rentalDateError($message) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit($message);
}
