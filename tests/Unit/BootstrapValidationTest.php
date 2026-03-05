<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BootstrapValidationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once TEST_PROJECT_ROOT . '/api/v1/_bootstrap.php';
    }

    public function testOnlyDigitsRemovesSymbols(): void
    {
        self::assertSame('11987654321', anateje_only_digits('(11) 98765-4321'));
    }

    public function testValidCpfAcceptsKnownValidCpf(): void
    {
        self::assertTrue(anateje_valid_cpf('52998224725'));
    }

    public function testValidCpfRejectsRepeatedDigits(): void
    {
        self::assertFalse(anateje_valid_cpf('11111111111'));
    }

    public function testSlugNormalizesAccentsAndSpacing(): void
    {
        $slug = anateje_slug(' Ação   Especial 2026 ');
        self::assertMatchesRegularExpression('/^ac[a-z-]*especial-2026$/', $slug);
    }

    public function testParseDatetimeSupportsDateAndDatetime(): void
    {
        self::assertSame('2026-03-05 00:00:00', anateje_parse_datetime('2026-03-05'));
        self::assertSame('2026-03-05 14:35:00', anateje_parse_datetime('2026-03-05 14:35'));
        self::assertNull(anateje_parse_datetime('texto-invalido'));
    }
}
