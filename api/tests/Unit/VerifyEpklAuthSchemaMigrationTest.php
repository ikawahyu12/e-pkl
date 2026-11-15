<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class VerifyEpklAuthSchemaMigrationTest extends TestCase
{
    #[DataProvider('checkExpressions')]
    public function test_check_expression_matches_exactly_one_profile_rule(
        string $expression,
        bool $expected,
    ): void {
        $migration = require __DIR__.'/../../database/migrations/2026_11_01_163239_verify_epkl_auth_schema.php';
        $parser = new ReflectionMethod($migration, 'isExactlyOneProfileCheck');

        self::assertSame($expected, $parser->invoke($migration, $expression));
    }

    public static function checkExpressions(): array
    {
        return [
            'student then teacher XOR' => [
                '(siswa_id IS NOT NULL AND guru_id IS NULL) OR (siswa_id IS NULL AND guru_id IS NOT NULL)',
                true,
            ],
            'teacher then student XOR' => [
                '(guru_id IS NOT NULL AND siswa_id IS NULL) OR (guru_id IS NULL AND siswa_id IS NOT NULL)',
                true,
            ],
            'AND precedence without wrapping parentheses' => [
                'siswa_id IS NOT NULL AND guru_id IS NULL OR siswa_id IS NULL AND guru_id IS NOT NULL',
                true,
            ],
            'nested parentheses whitespace and quoted identifiers' => [
                " ( ( `SISWA_ID` IS NOT NULL  AND `GURU_ID` IS NULL ) OR\n"
                    ." ( `SISWA_ID` IS NULL AND `GURU_ID` IS NOT NULL ) ) ",
                true,
            ],
            'allows both columns to be null' => [
                '(siswa_id IS NULL AND guru_id IS NULL) OR (siswa_id IS NOT NULL AND guru_id IS NOT NULL)',
                false,
            ],
            'allows both columns to be populated' => [
                'siswa_id IS NOT NULL OR guru_id IS NOT NULL',
                false,
            ],
            'only requires student profile' => [
                'siswa_id IS NOT NULL',
                false,
            ],
            'only requires teacher profile' => [
                'guru_id IS NOT NULL',
                false,
            ],
            'depends on numeric role id' => [
                '(role_id = 2 AND siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                    .'(role_id = 3 AND siswa_id IS NULL AND guru_id IS NOT NULL)',
                false,
            ],
            'unsupported operator' => [
                'siswa_id IS NOT NULL XOR guru_id IS NOT NULL',
                false,
            ],
            'unbalanced parentheses' => [
                '(siswa_id IS NOT NULL AND guru_id IS NULL OR siswa_id IS NULL AND guru_id IS NOT NULL',
                false,
            ],
            'trailing token' => [
                '(siswa_id IS NOT NULL AND guru_id IS NULL) OR '
                    .'(siswa_id IS NULL AND guru_id IS NOT NULL) unexpected',
                false,
            ],
        ];
    }
}
