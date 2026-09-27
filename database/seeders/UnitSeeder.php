<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => "M'", 'name' => 'Meter Panjang', 'group' => 'panjang', 'is_base_unit' => true],
            ['code' => 'M2', 'name' => 'Meter Persegi', 'group' => 'luas', 'is_base_unit' => true],
            ['code' => 'M3', 'name' => 'Meter Kubik', 'group' => 'volume', 'is_base_unit' => true],
            ['code' => 'Kg', 'name' => 'Kilogram', 'group' => 'berat', 'is_base_unit' => true],
            ['code' => 'Ton', 'name' => 'Ton', 'group' => 'berat', 'is_base_unit' => false],
            ['code' => 'Ls', 'name' => 'Lump Sum', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Bh', 'name' => 'Buah', 'group' => 'satuan_hitung', 'is_base_unit' => true],
            ['code' => 'Psg', 'name' => 'Pasang', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Org', 'name' => 'Orang / Hari', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Lb', 'name' => 'Lembar', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Titik', 'name' => 'Titik Instalasi', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Phase', 'name' => 'Phase / Tahap', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Zak', 'name' => 'Zak / Sak (50kg)', 'group' => 'berat', 'is_base_unit' => false],
            ['code' => 'Batang', 'name' => 'Batang (12m)', 'group' => 'panjang', 'is_base_unit' => false],
            ['code' => 'Roll', 'name' => 'Roll', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Ltr', 'name' => 'Liter', 'group' => 'volume', 'is_base_unit' => false],
            ['code' => 'Unit', 'name' => 'Unit', 'group' => 'satuan_hitung', 'is_base_unit' => false],
            ['code' => 'Set', 'name' => 'Set', 'group' => 'satuan_hitung', 'is_base_unit' => false],
        ];

        foreach ($units as $u) {
            Unit::updateOrCreate(['code' => $u['code']], $u);
        }
    }
}
