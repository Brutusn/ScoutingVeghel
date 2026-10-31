<?php
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/dates.php';

$_POST['d'] = '1';
$start = rentalPostDate();
if (!$start) {
    rentalDateError('Geen geldige datum opgegeven.');
}
rentalJsonResponse(getReservations($start, (clone $start)->modify('first day of next month')));
