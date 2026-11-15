<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ActivePlacementGuardExpressionTest extends TestCase
{
    #[DataProvider('expressions')]
    public function test_guard_expression_matches_only_the_expected_logic(
        string $expression,
        bool $expected,
    ): void {
        $migration = require __DIR__.'/../../database/migrations/2026_11_14_040730_add_active_placement_guard_to_penempatan_pkl_table.php';
        $parser = new ReflectionMethod($migration, 'isExpectedGuardExpression');

        self::assertSame($expected, $parser->invoke($migration, $expression));
    }

    public static function expressions(): array
    {
        return [
            'migration expression' => [
                "CASE WHEN status IN ('belum_mulai', 'aktif') THEN 1 ELSE NULL END",
                true,
            ],
            'MySQL 8.0.30 metadata expression' => [
                "CASE WHEN (`status` IN (_utf8mb4'belum_mulai', _utf8mb4'aktif')) THEN 1 ELSE NULL END",
                true,
            ],
            'additional condition parentheses' => [
                "CASE WHEN ((`status` IN (_utf8mb4'belum_mulai', _utf8mb4'aktif'))) THEN 1 ELSE NULL END",
                true,
            ],
            'outer expression parentheses' => [
                "((CASE WHEN (`status` IN (_utf8mb4'belum_mulai', _utf8mb4'aktif')) THEN 1 ELSE NULL END))",
                true,
            ],
            'different status' => [
                "CASE WHEN status IN ('belum_mulai', 'selesai') THEN 1 ELSE NULL END",
                false,
            ],
            'wrong literal capitalization' => [
                "CASE WHEN status IN ('belum_mulai', 'AKTIF') THEN 1 ELSE NULL END",
                false,
            ],
            'different operator' => [
                "CASE WHEN status = 'aktif' THEN 1 ELSE NULL END",
                false,
            ],
            'wrong THEN result' => [
                "CASE WHEN status IN ('belum_mulai', 'aktif') THEN 2 ELSE NULL END",
                false,
            ],
            'wrong ELSE result' => [
                "CASE WHEN status IN ('belum_mulai', 'aktif') THEN 1 ELSE 0 END",
                false,
            ],
            'additional OR condition' => [
                "CASE WHEN status IN ('belum_mulai', 'aktif') OR status = 'selesai' THEN 1 ELSE NULL END",
                false,
            ],
            'unbalanced condition parentheses' => [
                "CASE WHEN ((status IN ('belum_mulai', 'aktif')) THEN 1 ELSE NULL END",
                false,
            ],
        ];
    }
}
