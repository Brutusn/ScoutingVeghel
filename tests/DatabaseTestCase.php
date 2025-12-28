<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../php/db_layer.php';

abstract class DatabaseTestCase extends TestCase
{
    protected mysqli $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = databaseMYSQLi();
        $this->wipeTables();
    }

    protected function tearDown(): void
    {
        $this->db->close();
        parent::tearDown();
    }

    private function wipeTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $tables = [
            'verhuur_verhuring',
            'verhuur_reservering',
            'verhuur_huurder',
            'verhuur_mutaties',
            'verhuur_notes',
            'verhuur_eigen_groep',
        ];

        foreach ($tables as $table) {
            $this->db->query('TRUNCATE TABLE ' . $table);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function insert(string $query, array $params = []): void
    {
        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare statement: ' . $this->db->error);
        }

        if ($params !== []) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to execute statement: ' . $stmt->error);
        }
        $stmt->close();
    }

    protected function createSampleHuurder(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : uniqid();
        return getHuurder(
            'Test Huurder ' . $suffix,
            'Contact ' . $suffix,
            $suffix . '@example.test',
            '0612345678',
            'Teststraat 1',
            '1234AB',
            'Veghel'
        );
    }

    protected function createSampleReservering(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : uniqid();
        $start = '2025-01-01 10:00:00';
        $end = '2025-01-02 12:00:00';
        $rid = getReservering('Activiteit ' . $suffix, $start, $end, '25');

        return [$rid, $start, $end];
    }

    protected function createFullReservationFlow(string $suffix = '', string $groupCode = ''): array
    {
        $hid = $this->createSampleHuurder($suffix);
        [$rid, $start, $end] = $this->createSampleReservering($suffix);
        createVerhuring($hid, $rid, $groupCode);
        $confirm = getConfirm($hid, $rid);
        $verhuring = getVerhuringFromConfirm($confirm);

        return [
            'huurder_id' => $hid,
            'reservering_id' => $rid,
            'verhuring_id' => $verhuring[0],
            'confirm' => $confirm,
            'start' => $start,
            'end' => $end,
        ];
    }
}
