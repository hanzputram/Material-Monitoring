<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MaterialMasterSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::all()->keyBy('code');

        $materials = [
            ['code' => 'MAT-SMN-001', 'name' => 'Semen Portland (PC) 50kg', 'category' => 'bahan_dasar', 'unit' => 'Zak', 'price' => 78000],
            ['code' => 'MAT-PSR-001', 'name' => 'Pasir Beton Cuci', 'category' => 'bahan_dasar', 'unit' => 'M3', 'price' => 320000],
            ['code' => 'MAT-PSR-002', 'name' => 'Pasir Pasang Ayak', 'category' => 'bahan_dasar', 'unit' => 'M3', 'price' => 290000],
            ['code' => 'MAT-SPL-001', 'name' => 'Batu Pecah / Split 2/3', 'category' => 'bahan_dasar', 'unit' => 'M3', 'price' => 350000],
            ['code' => 'MAT-BTN-001', 'name' => 'Ready Mix Beton K-300', 'category' => 'bahan_dasar', 'unit' => 'M3', 'price' => 1150000],
            ['code' => 'MAT-BTN-002', 'name' => 'Ready Mix Beton K-250', 'category' => 'bahan_dasar', 'unit' => 'M3', 'price' => 1050000],
            ['code' => 'MAT-BSI-001', 'name' => 'Besi Beton Ulir D16', 'category' => 'bahan_dasar', 'unit' => 'Kg', 'price' => 16500],
            ['code' => 'MAT-BSI-002', 'name' => 'Besi Beton Ulir D13', 'category' => 'bahan_dasar', 'unit' => 'Kg', 'price' => 16500],
            ['code' => 'MAT-BSI-003', 'name' => 'Besi Beton Polos Ø10', 'category' => 'bahan_dasar', 'unit' => 'Kg', 'price' => 15500],
            ['code' => 'MAT-BSI-004', 'name' => 'Besi Beton Polos Ø8', 'category' => 'bahan_dasar', 'unit' => 'Kg', 'price' => 15500],
            ['code' => 'MAT-BSI-005', 'name' => 'Kawat Beton / Bendrat', 'category' => 'alat_bantu', 'unit' => 'Kg', 'price' => 24000],
            ['code' => 'MAT-BKF-001', 'name' => 'Bekisting Kolom / Balok (Triplek + Kayu)', 'category' => 'alat_bantu', 'unit' => 'M2', 'price' => 195000],
            ['code' => 'MAT-TPL-001', 'name' => 'Triplek Phenolic 9mm', 'category' => 'bahan_dasar', 'unit' => 'Lb', 'price' => 145000],
            ['code' => 'MAT-KYU-001', 'name' => 'Kayu Bekisting 5/7 Meranti', 'category' => 'bahan_dasar', 'unit' => 'M3', 'price' => 3800000],
            ['code' => 'MAT-PKU-001', 'name' => 'Paku Campur 5 - 10cm', 'category' => 'alat_bantu', 'unit' => 'Kg', 'price' => 22000],
            ['code' => 'MAT-KRM-001', 'name' => 'Keramik Lantai 25x20 Kasar Anti-Slip', 'category' => 'bahan_jadi', 'unit' => 'M2', 'price' => 95000],
            ['code' => 'MAT-KRM-002', 'name' => 'Keramik Lantai 40x40 Polished', 'category' => 'bahan_jadi', 'unit' => 'M2', 'price' => 85000],
            ['code' => 'MAT-KRM-003', 'name' => 'Keramik Dinding 20x25 Glossy', 'category' => 'bahan_jadi', 'unit' => 'M2', 'price' => 98000],
            ['code' => 'MAT-MTR-001', 'name' => 'Semen Mortar Perekat Bata Ringan', 'category' => 'bahan_dasar', 'unit' => 'Zak', 'price' => 92000],
            ['code' => 'MAT-MTR-002', 'name' => 'Semen Mortar Acian Skimcoat 40kg', 'category' => 'bahan_dasar', 'unit' => 'Zak', 'price' => 85000],
            ['code' => 'MAT-CAT-001', 'name' => 'Cat Dasar Alkali Sealer', 'category' => 'bahan_jadi', 'unit' => 'Kg', 'price' => 45000],
            ['code' => 'MAT-CAT-002', 'name' => 'Cat Dinding Interior Dulux / Catylac', 'category' => 'bahan_jadi', 'unit' => 'Kg', 'price' => 38000],
            ['code' => 'MAT-CAT-003', 'name' => 'Cat Dinding Eksterior Weathershield', 'category' => 'bahan_jadi', 'unit' => 'Kg', 'price' => 85000],
            ['code' => 'MAT-PPA-001', 'name' => 'Pipa PVC AW 3" Wavin / Rucika', 'category' => 'bahan_jadi', 'unit' => 'Batang', 'price' => 210000],
            ['code' => 'MAT-PPA-002', 'name' => 'Pipa PVC AW 4" Wavin / Rucika', 'category' => 'bahan_jadi', 'unit' => 'Batang', 'price' => 340000],
            ['code' => 'MAT-PPA-003', 'name' => 'Pipa PVC AW 1/2" Air Bersih', 'category' => 'bahan_jadi', 'unit' => 'Batang', 'price' => 48000],
            ['code' => 'MAT-BIO-001', 'name' => 'Biofilter Septic Tank Biotech 2000 Liter', 'category' => 'bahan_jadi', 'unit' => 'Unit', 'price' => 14500000],
            ['code' => 'MAT-KUS-001', 'name' => 'Kusen Alumunium 4" Powder Coating', 'category' => 'bahan_jadi', 'unit' => 'M\'', 'price' => 125000],
            ['code' => 'MAT-PNT-001', 'name' => 'Daun Pintu Panel Engineering Wood', 'category' => 'bahan_jadi', 'unit' => 'Unit', 'price' => 1650000],
        ];

        foreach ($materials as $m) {
            $unitId = $units[$m['unit']]->id ?? 1;
            Material::updateOrCreate(
                ['code' => $m['code']],
                [
                    'name' => $m['name'],
                    'category' => $m['category'],
                    'default_unit_id' => $unitId,
                    'standard_price' => $m['price'],
                    'is_active' => true,
                ]
            );
        }
    }
}
