<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseOrderPdfService
{
    /**
     * Konversi bilangan angka ke kalimat bahasa Indonesia (Terbilang)
     */
    public static function toWords(float|int $number): string
    {
        $number = floor(abs($number));

        if ($number === 0.0 || $number === 0) {
            return 'Nol Rupiah';
        }

        $result = trim(preg_replace('/\s+/', ' ', self::convertNumber($number)));

        return $result ? $result.' Rupiah' : 'Nol Rupiah';
    }

    protected static function convertNumber(float|int $n): string
    {
        $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

        if ($n < 12) {
            return ' '.$words[(int) $n];
        }
        if ($n < 20) {
            return self::convertNumber($n - 10).' Belas';
        }
        if ($n < 100) {
            return self::convertNumber((int) ($n / 10)).' Puluh'.self::convertNumber($n % 10);
        }
        if ($n < 200) {
            return ' Seratus'.self::convertNumber($n - 100);
        }
        if ($n < 1000) {
            return self::convertNumber((int) ($n / 100)).' Ratus'.self::convertNumber($n % 100);
        }
        if ($n < 2000) {
            return ' Seribu'.self::convertNumber($n - 1000);
        }
        if ($n < 1000000) {
            return self::convertNumber((int) ($n / 1000)).' Ribu'.self::convertNumber($n % 1000);
        }
        if ($n < 1000000000) {
            return self::convertNumber((int) ($n / 1000000)).' Juta'.self::convertNumber($n % 1000000);
        }
        if ($n < 1000000000000) {
            return self::convertNumber((int) ($n / 1000000000)).' Miliar'.self::convertNumber(fmod($n, 1000000000));
        }
        if ($n < 1000000000000000) {
            return self::convertNumber((int) ($n / 1000000000000)).' Triliun'.self::convertNumber(fmod($n, 1000000000000));
        }

        return '';
    }

    /**
     * Generate objek PDF dari Purchase Order
     */
    public function generate(PurchaseOrder $po): \Barryvdh\DomPDF\PDF
    {
        $po->loadMissing([
            'project',
            'supplier',
            'creator',
            'items.material',
            'items.unit',
            'items.rabItem.rabNode',
        ]);

        $totalAmount = $po->items->sum(fn ($it) => (float) $it->qty_ordered * (float) $it->unit_price);
        $terbilang = self::toWords($totalAmount);

        $pdf = Pdf::loadView('procurement.pdf.po', [
            'po' => $po,
            'project' => $po->project,
            'supplier' => $po->supplier,
            'creator' => $po->creator,
            'items' => $po->items,
            'totalAmount' => $totalAmount,
            'terbilang' => $terbilang,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }
}
