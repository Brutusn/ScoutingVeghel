<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../php/date_layer.php';

final class DateLayerTest extends TestCase
{
    /**
     * @dataProvider monthNameProvider
     */
    public function testGetMonthNumberMatchesDutchNames(string $name, int $expected): void
    {
        $this->assertSame($expected, getMonthNumber($name));
    }

    public static function monthNameProvider(): array
    {
        return [
            ['januari', 1],
            ['FEBRUARI', 2],
            ['Maart', 3],
            ['april', 4],
            ['mei', 5],
            ['juni', 6],
            ['juli', 7],
            ['augustus', 8],
            ['september', 9],
            ['oktober', 10],
            ['november', 11],
            ['december', 12],
            ['onbekend', -1],
        ];
    }

    public function testValidDateAcceptsReasonableDate(): void
    {
        $this->assertTrue(validDate(2025, 10, 31, 12, 30));
    }

    public function testValidDateRejectsInvalidMinute(): void
    {
        $this->assertFalse(validDate(2025, 10, 31, 12, 75));
    }

    public function testValidDateRejectsInvalidDay(): void
    {
        $this->assertFalse(validDate(2025, 2, 30, 12, 30));
    }
}
