<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../php/huurovereenkomst.php';

final class HuurovereenkomstTest extends TestCase
{
    public function testDisplayHuurovereenkomstRendersEssentialFields(): void
    {
        $html = displayHuurovereenkomst(
            'Scouting Veghel',
            'Beheerder',
            'Dorshout 29',
            'Veghel',
            '0413-350260',
            'Verhuur SV',
            '0413-350260',
            'NL69IBAN1234567890',
            'Test Huurder',
            '0612345678',
            'Teststraat 1',
            '1234AB',
            'Veghel',
            '2025-01-01 10:00:00',
            '2025-01-02 12:00:00',
            25,
            '2024-12-15',
            '2024-12-15',
            '2024-12-01',
            'WVB/1',
            'WVH/1',
            '<strong>75</strong>',
            '<strong>200</strong>'
        );

        $this->assertStringContainsString('Huurovereenkomst', $html);
        $this->assertStringContainsString('Test Huurder', $html);
        $this->assertStringContainsString('2025-01-01 10:00:00', $html);
        $this->assertStringContainsString('WVB/1', $html);
    }
}
