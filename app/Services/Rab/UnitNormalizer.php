<?php

namespace App\Services\Rab;

class UnitNormalizer
{
    /**
     * Canonical unit mapping dictionary.
     * Key is cleaned lowercase alias, value is canonical formatted unit.
     *
     * @var array<string, string>
     */
    public const MAP = [
        "m'" => "M'",
        'm1' => "M'",
        'm' => "M'",
        'mtr' => "M'",
        'meter' => "M'",
        'm2' => 'M2',
        'm3' => 'M3',
        'kg' => 'Kg',
        'ton' => 'Ton',
        'ls' => 'Ls',
        'lumpsum' => 'Ls',
        'lump sum' => 'Ls',
        'unit' => 'Unit',
        'zak' => 'Zak',
        'sak' => 'Zak',
        'batang' => 'Batang',
        'btg' => 'Batang',
        'buah' => 'Buah',
        'bh' => 'Buah',
        'pcs' => 'Buah',
        'lembar' => 'Lembar',
        'lbr' => 'Lembar',
        'liter' => 'Liter',
        'ltr' => 'Liter',
        'l' => 'Liter',
        'set' => 'Set',
        'titik' => 'Titik',
        'roll' => 'Roll',
        'dus' => 'Dus',
    ];

    /**
     * Normalize raw unit input to canonical unit.
     *
     * @return array{canonical: string|null, raw: string|null, is_known: bool}
     */
    public static function normalize(?string $raw): array
    {
        if ($raw === null) {
            return [
                'canonical' => null,
                'raw' => null,
                'is_known' => false,
            ];
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [
                'canonical' => null,
                'raw' => '',
                'is_known' => false,
            ];
        }

        // Clean: ² -> 2, ³ -> 3, ^ -> empty
        $cleaned = str_replace(['²', '³', '^'], ['2', '3', ''], $trimmed);
        $key = mb_strtolower(trim($cleaned));

        if (isset(self::MAP[$key])) {
            return [
                'canonical' => self::MAP[$key],
                'raw' => $trimmed,
                'is_known' => true,
            ];
        }

        return [
            'canonical' => $trimmed,
            'raw' => $trimmed,
            'is_known' => false,
        ];
    }
}
