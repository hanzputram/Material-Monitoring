<?php

use App\Helpers\NumberHelper;

if (! function_exists('format_qty')) {
    /**
     * Format kuantiti tanpa .0000 jika bilangan bulat
     */
    function format_qty($number, int $maxDecimals = 4): string
    {
        return NumberHelper::formatQty($number, $maxDecimals);
    }
}

if (! function_exists('format_rupiah')) {
    /**
     * Format rupiah rapi tanpa desimal jika bulat
     */
    function format_rupiah($number, bool $withPrefix = true): string
    {
        return NumberHelper::formatRupiah($number, $withPrefix);
    }
}
