<?php

require_once __DIR__ . '/DatabaseTestCase.php';

define('SV_INCLUDE_RESERVERING', true);
require_once __DIR__ . '/../php/Reservering.php';

define('SV_INCLUDE_RESERVERING_MONTH', true);
require_once __DIR__ . '/../php/ReserveringMonth.php';

define('SV_INCLUDE_RESERVERING_VERIFICATION', true);
require_once __DIR__ . '/../php/ReserveringVerification.php';

final class ReservationsFunctionsTest extends DatabaseTestCase
{
    public function testGetReservationsNextDaysReturnsReservationWithinWindow(): void
    {
        $this->createFullReservationFlow('nextdays');

        $reservations = getReservationsNextDays(1, 1, 2025);
        $this->assertCount(1, $reservations);
        $this->assertSame('2025-01-01', $reservations[0]['dayFrom']);
        $this->assertSame('2025-01-02', $reservations[0]['dayTo']);
    }

    public function testGetReservationsMonthReturnsEntriesForMonth(): void
    {
        $this->createFullReservationFlow('month');

        $reservations = getReservationsMonth(1, 2025);
        $this->assertCount(1, $reservations);
        $this->assertSame('2025-01-01', $reservations[0]['dayFrom']);
    }

    public function testGetReservationsDatesHonoursExactRange(): void
    {
        $this->createFullReservationFlow('range');

    $reservations = getReservationsDates(1, 1, 2025, 0, 0, 3, 1, 2025, 23, 59);
        $this->assertCount(1, $reservations);

    $outsideRange = getReservationsDates(5, 1, 2025, 0, 0, 6, 1, 2025, 23, 59);
        $this->assertCount(0, $outsideRange);
    }
}
