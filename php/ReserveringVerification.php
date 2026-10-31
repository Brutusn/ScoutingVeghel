<?php
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/dates.php';

$start = rentalPostDate('1');
$end = rentalPostDate('2', true);
if (!$start || !$end || $end < $start) {
    rentalDateError('Geen geldige data opgegeven.');
}
rentalJsonResponse(getReservations($start, $end));
