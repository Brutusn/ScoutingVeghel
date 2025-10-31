<?php

use DateTime;

require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/../php/kostenberekening_nieuw.php';

final class DbLayerTest extends DatabaseTestCase
{
    public function testGetHuurderCreatesAndReturnsId(): void
    {
        $hid = $this->createSampleHuurder('nieuw');
        $this->assertGreaterThan(0, $hid);

        $hidAgain = $this->createSampleHuurder('nieuw');
        $this->assertSame($hid, $hidAgain, 'Should return same id for existing huurder');
    }

    public function testHuurderIdFromCodeMatchesMapping(): void
    {
        $hid = $this->createSampleHuurder('groep');
        $this->insert('INSERT INTO verhuur_eigen_groep (code, huurder_id) VALUES (?, ?)', ['sv', $hid]);

        $found = getHuurderIDFromCode('sv');
        $this->assertSame($hid, $found);
    }

    public function testGetInfoFromHidReturnsNameAndMail(): void
    {
        $hid = $this->createSampleHuurder('info');

        $info = getInfoFromHid($hid);
        $this->assertSame('Test Huurder info', $info[0]);
        $this->assertSame('info@example.test', $info[1]);
    }

    public function testGetReserveringCreatesRow(): void
    {
        [$rid] = $this->createSampleReservering('res');
        $this->assertGreaterThan(0, $rid);

        $result = $this->db->query('SELECT COUNT(*) AS total FROM verhuur_reservering');
        $countRow = $result->fetch_assoc();
        $this->assertSame(1, (int) $countRow['total']);
    }

    public function testGetReservationsReturnsExpectedWindow(): void
    {
        $flow = $this->createFullReservationFlow('window');

        $start = new DateTime('2024-12-31 00:00:00');
        $end = new DateTime('2025-02-01 00:00:00');

        $reservations = getReservations($start, $end);
        $this->assertCount(1, $reservations);
        $this->assertSame('2025-01-01', $reservations[0]['dayFrom']);
        $this->assertSame('2025-01-02', $reservations[0]['dayTo']);
        $this->assertFalse($reservations[0]['bySV']);

        // Mark as SV group and ensure flag flips
        $this->db->query("UPDATE verhuur_verhuring SET groep = 'SV' WHERE id = " . $flow['verhuring_id']);
        $reservations = getReservations($start, $end);
        $this->assertTrue($reservations[0]['bySV']);
    }

    public function testReserveringConfirmationFlow(): void
    {
        $flow = $this->createFullReservationFlow('confirm');

        $this->assertTrue(isReserveringConfirmable($flow['reservering_id']));

        reserveringConfirmed($flow['reservering_id']);

        $result = $this->db->query('SELECT status_id FROM verhuur_reservering WHERE id = ' . $flow['reservering_id']);
        $status = $result->fetch_assoc();
        $this->assertSame(1, (int) $status['status_id']);

        $this->assertFalse(isReserveringConfirmable($flow['reservering_id']));
    }

    public function testAlreadyReservedDetectsOverlap(): void
    {
        $flow = $this->createFullReservationFlow('overlap');

        $start = new DateTime('2025-01-01 09:00:00');
        $end = new DateTime('2025-01-01 18:00:00');
        $this->assertTrue(alreadyReserved($start, $end));

        $start = new DateTime('2025-01-10 09:00:00');
        $end = new DateTime('2025-01-11 18:00:00');
        $this->assertFalse(alreadyReserved($start, $end));
    }

    public function testCreateVerhuringGeneratesConfirmHash(): void
    {
        $flow = $this->createFullReservationFlow('hash', 'groepscode');

        $this->assertNotEmpty($flow['confirm']);

        $result = $this->db->query('SELECT groep FROM verhuur_verhuring WHERE id = ' . $flow['verhuring_id']);
        $row = $result->fetch_assoc();
        $this->assertSame('groepscode', $row['groep']);
    }

    public function testGetHuurovereenkomstDataReturnsExpectedFields(): void
    {
        $flow = $this->createFullReservationFlow('huurovereenkomst');
        $data = getHuurovereenkomstData($flow['verhuring_id']);

        $this->assertSame('Test Huurder huurovereenkomst', $data['huurder_naam']);
        $this->assertSame('Teststraat 1', $data['huurder_adres']);
        $this->assertSame('2025-01-01 10:00:00', $data['verhuring_begin_datum']);
        $this->assertSame('2025-01-02 12:00:00', $data['verhuring_eind_datum']);
        $this->assertArrayHasKey('verhuring_borg_limiet', $data);
    }
}
