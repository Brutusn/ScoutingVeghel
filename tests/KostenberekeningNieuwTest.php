<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../php/kostenberekening_nieuw.php';

final class KostenberekeningNieuwTest extends TestCase
{
    public function testGetDifferenceInHoursRoundsUpPartialHours(): void
    {
        $hours = getDifferenceInHours('2025-01-01 10:00:00', '2025-01-01 12:30:00');
        $this->assertSame(3, $hours);
    }

    public function testGetDifferenceInHoursCountsFullDays(): void
    {
        $hours = getDifferenceInHours('2025-01-01 10:00:00', '2025-01-02 09:00:00');
        $this->assertSame(23, $hours);
    }

    public function testGetDifferenceInDaysUsesDayComponentOnly(): void
    {
        $days = getDifferenceInDays('2025-01-01 00:00:00', '2025-01-05 23:59:59');
        $this->assertSame(4, $days);
    }

    public function testGetBorgReturnsDagdeelForZeroDays(): void
    {
        $this->assertSame(BEDRAG_BORG_DAGDEEL, getBorg(0));
    }

    public function testGetBorgReturnsNachtForSingleDay(): void
    {
        $this->assertSame(BEDRAG_BORG_NACHT, getBorg(1));
    }

    public function testGetBorgReturnsLangerForMultipleDays(): void
    {
        $this->assertSame(BEDRAG_BORG_LANGER, getBorg(5));
    }

    public function testGetKostenCalculatesHourlyRental(): void
    {
        $cost = getKosten(0, 3, 10);
        $expected = (3 * KOSTEN_PER_UUR) + BEDRAG_BORG_DAGDEEL;
        $this->assertSame($expected, $cost);
    }

    public function testGetKostenUpgradesToDailyWhenHoursExceedLimit(): void
    {
        $cost = getKosten(0, 12, 10);
        $expected = (1 * 10 * KOSTEN_PPPN) + BEDRAG_BORG_NACHT;
        $this->assertSame($expected, $cost);
    }

    public function testGetKostenCalculatesMultiDayRental(): void
    {
        $cost = getKosten(3, 0, 20);
        $expected = (3 * 20 * KOSTEN_PPPN) + BEDRAG_BORG_LANGER;
        $this->assertSame($expected, $cost);
    }
}
