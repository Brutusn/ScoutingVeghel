<?php

/**
 * Converts the month name to month number
 *
 * @param $monthName The string representation of the Month (Duthc names)
 * @return The number of the month from 1 to 12
 */
function getMonthNumber($monthName){
    $monthName = strtolower($monthName);
    switch ($monthName) {
        case "januari":
            return 1;
        case "februari":
            return 2;
        case "maart":
            return 3;
        case "april":
            return 4;
        case "mei":
            return 5;
        case "juni":
            return 6;
        case "juli":
            return 7;
        case "augustus":
            return 8;
        case "september":
            return 9;
        case "oktober":
            return 10;
        case "november":
            return 11;
        case "december":
            return 12;
        default:
            return -1;
    }
}

/**
 * Check whether the date that is given is valid (basic checks)
 *
 * @param $year The year number
 * @param $month The month number
 * @param $day The day number of the month
 * @param $hour The hour of the day (24h)
 * @param $minute The minute of the hour
 * @return true of valid, false otherwise
 */
function validDate($year, $month, $day, $hour, $minute){
    $y = filter_var($year, FILTER_VALIDATE_INT);
    $m = filter_var($month, FILTER_VALIDATE_INT);
    $d = filter_var($day, FILTER_VALIDATE_INT);
    $h = filter_var($hour, FILTER_VALIDATE_INT);
    $min = filter_var($minute, FILTER_VALIDATE_INT);

    if($d <= 31 && $d > 0 && $m <= 12 && $m > 0 && $y > 0 && $h <= 24 && $h >= 0 && $m <= 60 && $m >= 0) {
        return true;
    } else {
        return false;
    }
}

?>
