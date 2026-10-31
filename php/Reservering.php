<?php
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/dates.php';

$start = rentalPostDate();
if (!$start) {
    rentalDateError('Geen geldige datum opgegeven.');
}
// Clamp the day to the following month, including leap years.
$end = (clone $start)->modify('first day of next month');
$day = min((int) $start->format('j'), (int) $end->format('t'));
$end->setDate((int) $end->format('Y'), (int) $end->format('n'), $day)->setTime(23, 59, 59);
rentalJsonResponse(getReservations($start, $end));
