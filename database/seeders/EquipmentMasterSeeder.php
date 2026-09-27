<?php

namespace Database\Seeders;

use App\Models\EquipmentCategory;
use App\Models\EquipmentMaster;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class EquipmentMasterSeeder extends Seeder
{
    public function run(): void
    {
        $catPeralatan = EquipmentCategory::firstOrCreate(['name' => 'Peralatan']);
        $catMesin = EquipmentCategory::firstOrCreate(['name' => 'Mesin']);
        $catAlatBerat = EquipmentCategory::firstOrCreate(['name' => 'Alat Berat']);

        $unitUnit = Unit::where('code', 'Unit')->first()?->id ?? 1;
        $unitSet = Unit::where('code', 'Set')->first()?->id ?? 1;
        $unitBh = Unit::where('code', 'Bh')->first()?->id ?? 1;

        $items = [
            // Peralatan
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-001', 'name' => 'Filter Exa (Excavator Filter Kit)', 'unit_id' => $unitSet, 'price' => 650000, 'spec' => 'Cartridge Filter Oli & Solar OEM'],
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-002', 'name' => 'Ban Luar Truk 1000-20', 'unit_id' => $unitBh, 'price' => 3250000, 'spec' => 'Radial 16 PR Heavy Duty'],
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-003', 'name' => 'Ban Dalam Truk 1000-20', 'unit_id' => $unitBh, 'price' => 450000, 'spec' => 'Karet Sintetis Flap Included'],
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-004', 'name' => 'Scaffolding Main Frame 170cm Set', 'unit_id' => $unitSet, 'price' => 45000, 'spec' => '2 Mainframe, 2 Cross Brace, 4 Joint Pin'],
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-005', 'name' => 'Theodolite Digital Laser', 'unit_id' => $unitUnit, 'price' => 350000, 'spec' => 'Akurasi 2 detik + Rambu Ukur'],
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-006', 'name' => 'Total Station Topcon / Sokkia', 'unit_id' => $unitUnit, 'price' => 750000, 'spec' => 'Prisma Tunggal + Tripod Alumunium'],
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-007', 'name' => 'Waterpass Otomatis Auto Level', 'unit_id' => $unitUnit, 'price' => 250000, 'spec' => 'Perbesaran 32x + Tripod'],
            ['cat_id' => $catPeralatan->id, 'code' => 'EQP-PLT-008', 'name' => 'Trafo Las Listrik Inverter 250A', 'unit_id' => $unitUnit, 'price' => 175000, 'spec' => 'IGBT Inverter 220V/380V'],

            // Mesin
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-001', 'name' => 'Genset Silent 15 KVA', 'unit_id' => $unitUnit, 'price' => 650000, 'spec' => 'Diesel Yanmar / Perkins 3 Phase'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-002', 'name' => 'Genset Silent 50 KVA', 'unit_id' => $unitUnit, 'price' => 1500000, 'spec' => 'Diesel Cummins 3 Phase 380V'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-003', 'name' => 'Mesin Molen Beton / Concrete Mixer 500L', 'unit_id' => $unitUnit, 'price' => 350000, 'spec' => 'Kapasitas 1 Sak Semen Diesel 8 PK'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-004', 'name' => 'Mesin Pompa Air 3 Inch Alkon', 'unit_id' => $unitUnit, 'price' => 180000, 'spec' => 'Bensin Honda GX160 Head 30m'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-005', 'name' => 'Mesin Pompa Submersible Celup 2 Inch', 'unit_id' => $unitUnit, 'price' => 150000, 'spec' => 'Dewatering Pump 1.5 kW'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-006', 'name' => 'Concrete Vibrator Engine + Selang 4m', 'unit_id' => $unitSet, 'price' => 165000, 'spec' => 'Head 38mm Gasoline Engine'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-007', 'name' => 'Mesin Bar Bender Rebar (Tekuk Besi D32)', 'unit_id' => $unitUnit, 'price' => 450000, 'spec' => 'Electric 3 Phase Kapasitas D32'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-008', 'name' => 'Mesin Bar Cutter Rebar (Potong Besi D32)', 'unit_id' => $unitUnit, 'price' => 450000, 'spec' => 'Electric 3 Phase Kapasitas D32'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-009', 'name' => 'Tamping Rammer / Stamper Kuda', 'unit_id' => $unitUnit, 'price' => 220000, 'spec' => 'Impact Force 14 kN Robin Engine'],
            ['cat_id' => $catMesin->id, 'code' => 'EQP-MSN-010', 'name' => 'Plate Compactor / Stamper Kodok', 'unit_id' => $unitUnit, 'price' => 200000, 'spec' => 'Exciting Force 15 kN'],

            // Alat Berat
            ['cat_id' => $catAlatBerat->id, 'code' => 'EQP-BRT-001', 'name' => 'Excavator Standar PC200', 'unit_id' => $unitUnit, 'price' => 375000, 'spec' => 'Bucket 0.93 m3 Komatsu / Cat'],
            ['cat_id' => $catAlatBerat->id, 'code' => 'EQP-BRT-002', 'name' => 'Dump Truck Tronton 10 Roda (24 Ton)', 'unit_id' => $unitUnit, 'price' => 1850000, 'spec' => 'Hino FM 260 Ti Kapasitas 20 m3'],
            ['cat_id' => $catAlatBerat->id, 'code' => 'EQP-BRT-003', 'name' => 'Tower Crane Jib 50m', 'unit_id' => $unitUnit, 'price' => 45000000, 'spec' => 'Max Load 6 Ton Tip Load 1.3 Ton'],
            ['cat_id' => $catAlatBerat->id, 'code' => 'EQP-BRT-004', 'name' => 'Mobile Crane 25 Ton Tadano', 'unit_id' => $unitUnit, 'price' => 8500000, 'spec' => 'Boom Teleskopik 31m'],
            ['cat_id' => $catAlatBerat->id, 'code' => 'EQP-BRT-005', 'name' => 'Mesin Bored Pile Hidrolik Sany', 'unit_id' => $unitUnit, 'price' => 15000000, 'spec' => 'Kedalaman bor hingga 30m'],
        ];

        foreach ($items as $item) {
            EquipmentMaster::updateOrCreate(
                ['code' => $item['code']],
                [
                    'equipment_category_id' => $item['cat_id'],
                    'name' => $item['name'],
                    'default_unit_id' => $item['unit_id'],
                    'price' => $item['price'] ?? 0,
                    'spec' => $item['spec'],
                    'is_active' => true,
                ]
            );
        }
    }
}
