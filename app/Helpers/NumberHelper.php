<?php

namespace App\Helpers;

class NumberHelper
{
    /**
     * Format kuantiti/angka:
     * - Jika tidak ada desimal (misal 43.0000 atau 6800.0000), tampilkan angka bulat bersih tanpa .0000
     * - Jika ada desimal (misal 21.2100 atau 6127.0400), tampilkan koma desimal hanya sejumlah yang diperlukan (tanpa trailing zero berlebih)
     */
    public static function formatQty($number, int $maxDecimals = 4): string
    {
        if ($number === null || $number === '') {
            return '0';
        }

        $val = (float) $number;

        // Jika angka bulat utuh (contoh 43, 6800)
        if (floor($val) == $val) {
            return number_format($val, 0, ',', '.');
        }

        // Jika terdapat bagian desimal (contoh 21.21, 6127.04)
        $formatted = number_format($val, $maxDecimals, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    }

    /**
     * Format rupiah bersih tanpa desimal jika bulat
     */
    public static function formatRupiah($number, bool $withPrefix = true): string
    {
        if ($number === null || $number === '') {
            return $withPrefix ? 'Rp 0' : '0';
        }

        $val = (float) $number;
        $prefix = $withPrefix ? 'Rp ' : '';

        if (floor($val) == $val) {
            return $prefix . number_format($val, 0, ',', '.');
        }

        $formatted = number_format($val, 2, ',', '.');
        $trimmed = rtrim(rtrim($formatted, '0'), ',');
        return $prefix . $trimmed;
    }
}
