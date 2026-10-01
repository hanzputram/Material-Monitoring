<?php

namespace Database\Seeders;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\EquipmentMaster;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\MaterialVarianceValidation;
use App\Models\Project;
use App\Models\ProjectEquipment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SampleProjectSeeder extends Seeder
{
    public function run(): void
    {
        $pmUser = User::where('email', 'pm@konstruksi.id')->first();
        $pengawasUser = User::where('email', 'pengawas@konstruksi.id')->first();
        $purchasingUser = User::where('email', 'purchasing@konstruksi.id')->first();

        // 1. Create Project
        $project = Project::updateOrCreate(
            ['name' => 'Review Rumah Susun (Prototipe)'],
            [
                'prototype_type' => 'Barak Rembunai',
                'floor_count' => 3,
                'budget_year' => '2026',
                'location_kds' => 'Kawasan Rembunai Blok A',
                'foundation_type' => 'Bored Pile',
                'status' => 'active',
                'alert_over_threshold_pct' => 10.00,
                'alert_under_threshold_pct' => 5.00,
                'created_by' => $pmUser?->id,
            ]
        );

        // Assign users to project
        $project->users()->syncWithoutDetaching([
            $pmUser->id => ['role_in_project' => 'Project Manager'],
            $pengawasUser->id => ['role_in_project' => 'Pengawas Lapangan'],
            $purchasingUser->id => ['role_in_project' => 'Purchasing Officer'],
        ]);

        // Units
        $unitM = Unit::where('code', "M'")->first()->id;
        $unitM2 = Unit::where('code', 'M2')->first()->id;
        $unitM3 = Unit::where('code', 'M3')->first()->id;
        $unitKg = Unit::where('code', 'Kg')->first()->id;
        $unitLs = Unit::where('code', 'Ls')->first()->id;
        $unitUnit = Unit::where('code', 'Unit')->first()->id;
        $unitZak = Unit::where('code', 'Zak')->first()->id;
        $unitBatang = Unit::where('code', 'Batang')->first()->id;

        // Materials
        $matBetonK300 = Material::where('code', 'MAT-BTN-001')->first();
        $matBesiUlir = Material::where('code', 'MAT-BSI-001')->first();
        $matBekisting = Material::where('code', 'MAT-BKF-001')->first();
        $matKeramik2520 = Material::where('code', 'MAT-KRM-001')->first();
        $matSemen = Material::where('code', 'MAT-SMN-001')->first();
        $matPasirPasang = Material::where('code', 'MAT-PSR-002')->first();
        $matBiofilter = Material::where('code', 'MAT-BIO-001')->first();
        $matPipa4 = Material::where('code', 'MAT-PPA-002')->first();

        // 2. RAB Structure
        // Level 1: I. PEKERJAAN PERSIAPAN
        $node1 = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'code' => 'I'],
            ['name' => 'PEKERJAAN PERSIAPAN STANDAR DAN RK3K KONSTRUKSI', 'level' => 1, 'sort_order' => 1]
        );

        $node1A = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'parent_id' => $node1->id, 'code' => 'I.A'],
            ['name' => 'PEKERJAAN PERSIAPAN', 'level' => 2, 'sort_order' => 1]
        );

        $item1A1 = RabItem::updateOrCreate(
            ['rab_node_id' => $node1A->id, 'item_no' => '1.0'],
            [
                'name' => 'Pengukuran dan Pemasangan Bouwplank',
                'volume' => 200,
                'unit_id' => $unitM,
                'unit_price' => 64200,
                'total_price' => 200 * 64200,
                'is_composite' => false,
                'sort_order' => 1,
            ]
        );

        $item1A2 = RabItem::updateOrCreate(
            ['rab_node_id' => $node1A->id, 'item_no' => '2.0'],
            [
                'name' => 'Pembuatan Direksi Keet & Bangsal Kerja',
                'volume' => 24,
                'unit_id' => $unitM2,
                'unit_price' => 1250000,
                'total_price' => 24 * 1250000,
                'is_composite' => false,
                'sort_order' => 2,
            ]
        );

        $node1B = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'parent_id' => $node1->id, 'code' => 'I.B'],
            ['name' => 'PEKERJAAN RK3K KONSTRUKSI', 'level' => 2, 'sort_order' => 2]
        );

        $item1B1 = RabItem::updateOrCreate(
            ['rab_node_id' => $node1B->id, 'item_no' => '1.0'],
            [
                'name' => 'Penyiapan Rencana K3 Konstruksi & Perlengkapan APD',
                'volume' => 1,
                'unit_id' => $unitLs,
                'unit_price' => 15000000,
                'total_price' => 15000000,
                'is_composite' => false,
                'sort_order' => 1,
            ]
        );

        // Level 1: II. PEKERJAAN STRUKTUR
        $node2 = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'code' => 'II'],
            ['name' => 'PEKERJAAN STRUKTUR', 'level' => 1, 'sort_order' => 2]
        );

        $node2A = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'parent_id' => $node2->id, 'code' => 'II.A'],
            ['name' => 'PEKERJAAN STRUKTUR LANTAI 1', 'level' => 2, 'sort_order' => 1]
        );

        $node2A1 = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'parent_id' => $node2A->id, 'code' => 'A.1'],
            ['name' => 'PEKERJAAN KOLOM & BALOK LANTAI 1', 'level' => 3, 'sort_order' => 1]
        );

        $item2A1_5 = RabItem::updateOrCreate(
            ['rab_node_id' => $node2A1->id, 'item_no' => '5.0'],
            [
                'name' => 'Kolom K1 (40x40) Struktur Utama',
                'volume' => 21.21,
                'unit_id' => $unitM3,
                'unit_price' => 9341400.75, // composite total per M3
                'total_price' => 198128237.32,
                'is_composite' => true,
                'sort_order' => 1,
            ]
        );

        // Level 5 BOM Breakdown for Kolom K1
        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item2A1_5->id, 'material_id' => $matBetonK300->id],
            [
                'volume' => 21.21,
                'unit_id' => $unitM3,
                'unit_price' => 1706697.58,
                'total_price' => 36202468.96,
                'input_by' => $purchasingUser?->id,
            ]
        );

        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item2A1_5->id, 'material_id' => $matBesiUlir->id],
            [
                'volume' => 6127.04,
                'unit_id' => $unitKg,
                'unit_price' => 19572.16,
                'total_price' => 119919435.36,
                'input_by' => $purchasingUser?->id,
            ]
        );

        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item2A1_5->id, 'material_id' => $matBekisting->id],
            [
                'volume' => 182.49,
                'unit_id' => $unitM2,
                'unit_price' => 230183.04,
                'total_price' => 42006333.00,
                'input_by' => $purchasingUser?->id,
            ]
        );

        // Level 1: III. ARSITEKTUR & SANITASI
        $node3 = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'code' => 'III'],
            ['name' => 'PEKERJAAN ARSITEKTUR & SANITASI', 'level' => 1, 'sort_order' => 3]
        );

        $node3A = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'parent_id' => $node3->id, 'code' => 'III.A'],
            ['name' => 'PEKERJAAN FINISHING & KERAMIK', 'level' => 2, 'sort_order' => 1]
        );

        $item3A1 = RabItem::updateOrCreate(
            ['rab_node_id' => $node3A->id, 'item_no' => '1.0'],
            [
                'name' => 'Pemasangan Keramik Lantai 25x20 Kasar Anti-Slip',
                'volume' => 85,
                'unit_id' => $unitM2,
                'unit_price' => 185000,
                'total_price' => 85 * 185000,
                'is_composite' => true,
                'sort_order' => 1,
            ]
        );

        // BOM for Keramik 25x20
        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item3A1->id, 'material_id' => $matKeramik2520->id],
            [
                'volume' => 95,
                'unit_id' => $unitM2,
                'unit_price' => 95000,
                'total_price' => 9025000,
                'input_by' => $purchasingUser?->id,
            ]
        );

        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item3A1->id, 'material_id' => $matSemen->id],
            [
                'volume' => 35,
                'unit_id' => $unitZak,
                'unit_price' => 78000,
                'total_price' => 2730000,
                'input_by' => $purchasingUser?->id,
            ]
        );

        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item3A1->id, 'material_id' => $matPasirPasang->id],
            [
                'volume' => 4.5,
                'unit_id' => $unitM3,
                'unit_price' => 290000,
                'total_price' => 1305000,
                'input_by' => $purchasingUser?->id,
            ]
        );

        $node3B = RabNode::updateOrCreate(
            ['project_id' => $project->id, 'parent_id' => $node3->id, 'code' => 'III.B'],
            ['name' => 'PEKERJAAN SANITASI & PLUMBING', 'level' => 2, 'sort_order' => 2]
        );

        $item3B1 = RabItem::updateOrCreate(
            ['rab_node_id' => $node3B->id, 'item_no' => '1.0'],
            [
                'name' => 'Pemasangan Biofilter Septic Tank Biotech 2000L Lengkap Pipa',
                'volume' => 1,
                'unit_id' => $unitUnit,
                'unit_price' => 18500000,
                'total_price' => 18500000,
                'is_composite' => true,
                'sort_order' => 1,
            ]
        );

        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item3B1->id, 'material_id' => $matBiofilter->id],
            [
                'volume' => 1,
                'unit_id' => $unitUnit,
                'unit_price' => 14500000,
                'total_price' => 14500000,
                'input_by' => $purchasingUser?->id,
            ]
        );

        RabItemMaterial::updateOrCreate(
            ['rab_item_id' => $item3B1->id, 'material_id' => $matPipa4->id],
            [
                'volume' => 6,
                'unit_id' => $unitBatang,
                'unit_price' => 340000,
                'total_price' => 2040000,
                'input_by' => $purchasingUser?->id,
            ]
        );

        // Recalculate subtotals
        $project->recalculateAllSubtotals();

        // 3. Suppliers
        $sup1 = Supplier::firstOrCreate(['name' => 'PT Holcim Semen Indonesia'], ['contact_person' => 'Bapak Joko', 'phone' => '081234567890', 'address' => 'Kawasan Industri Gresik']);
        $sup2 = Supplier::firstOrCreate(['name' => 'CV Baja Perkasa Sakti'], ['contact_person' => 'Ibu Ratna', 'phone' => '081398765432', 'address' => 'Cikarang Barat']);
        $sup3 = Supplier::firstOrCreate(['name' => 'PT Biotech Enviro Solusindo'], ['contact_person' => 'Bapak Irwan', 'phone' => '081122334455', 'address' => 'Tangerang']);

        // 4. Procurement: PO, DO, Invoice
        $po1 = PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO/2026/BR/001'],
            [
                'project_id' => $project->id,
                'supplier_id' => $sup2->id,
                'po_date' => Carbon::now()->subDays(10),
                'status' => 'sent',
                'created_by' => $purchasingUser?->id,
                'notes' => 'Pengadaan Besi Beton D16 Ulir untuk Kolom K1',
            ]
        );

        PurchaseOrderItem::updateOrCreate(
            ['purchase_order_id' => $po1->id, 'material_id' => $matBesiUlir->id],
            [
                'qty_ordered' => 6500,
                'unit_id' => $unitKg,
                'unit_price' => 19500,
            ]
        );

        // Pastikan dummy file bukti fisik tersedia di storage
        $this->ensureSampleAttachmentsExist();

        // DO received by Pengawas Lapangan
        $do1 = DeliveryOrder::updateOrCreate(
            ['do_number' => 'DO/BPS/2026-0891'],
            [
                'project_id' => $project->id,
                'purchase_order_id' => $po1->id,
                'supplier_id' => $sup2->id,
                'do_date' => Carbon::now()->subDays(8),
                'attachment_path' => 'attachments/do_sample_surat_jalan.pdf',
                'received_by' => $pengawasUser?->id,
                'status' => 'validated',
                'notes' => 'Barang diterima lengkap dan ditimbang di jembatan timbang proyek.',
            ]
        );

        DeliveryOrderItem::updateOrCreate(
            ['delivery_order_id' => $do1->id, 'material_id' => $matBesiUlir->id],
            [
                'qty_received' => 6800, // Delivered 6800 Kg vs planned 6127.04 Kg -> Kelebihan (+10.98%)
                'unit_id' => $unitKg,
            ]
        );

        // Invoice by Purchasing
        $inv1 = Invoice::updateOrCreate(
            ['invoice_number' => 'INV/BPS/26/0442'],
            [
                'project_id' => $project->id,
                'purchase_order_id' => $po1->id,
                'supplier_id' => $sup2->id,
                'invoice_date' => Carbon::now()->subDays(5),
                'amount' => 6800 * 19500,
                'attachment_path' => 'attachments/inv_sample_faktur.pdf',
                'validated_by' => $purchasingUser?->id,
                'status' => 'validated',
                'notes' => 'Sesuai dengan Surat Jalan DO/BPS/2026-0891',
            ]
        );

        // Non-RAB Purchase Sample (Pembelian di Luar RAB): Kawat Bendrat Pengikat Bekisting
        $matKawatBendrat = Material::where('code', 'MAT-BSI-005')->first();
        if ($matKawatBendrat) {
            $poNonRab = PurchaseOrder::updateOrCreate(
                ['po_number' => 'PO/2026/BR/002'],
                [
                    'project_id' => $project->id,
                    'supplier_id' => $sup2->id,
                    'po_date' => Carbon::now()->subDays(6),
                    'status' => 'sent',
                    'created_by' => $purchasingUser?->id,
                    'notes' => 'Pengadaan darurat di luar RAB: Kawat bendrat perkuatan bekisting kolom tambahan.',
                ]
            );

            PurchaseOrderItem::updateOrCreate(
                ['purchase_order_id' => $poNonRab->id, 'material_id' => $matKawatBendrat->id],
                [
                    'qty_ordered' => 100,
                    'unit_id' => $unitKg,
                    'unit_price' => 24000,
                ]
            );

            $doNonRab = DeliveryOrder::updateOrCreate(
                ['do_number' => 'DO/BPS/2026-0912'],
                [
                    'project_id' => $project->id,
                    'purchase_order_id' => $poNonRab->id,
                    'supplier_id' => $sup2->id,
                    'do_date' => Carbon::now()->subDays(3),
                    'attachment_path' => 'attachments/do_sample_surat_jalan.pdf',
                    'received_by' => $pengawasUser?->id,
                    'status' => 'validated',
                    'notes' => 'Barang tiba di lapangan dan diverifikasi sesuai fisik pengiriman.',
                ]
            );

            DeliveryOrderItem::updateOrCreate(
                ['delivery_order_id' => $doNonRab->id, 'material_id' => $matKawatBendrat->id],
                [
                    'qty_received' => 100,
                    'unit_id' => $unitKg,
                ]
            );
        }

        // Trigger Realization and Variance calculation
        MaterialRealization::recalculateAllForProject($project->id);

        // Create Variance Validation entry
        $realizationBesi = MaterialRealization::where('project_id', $project->id)
            ->where('material_id', $matBesiUlir->id)
            ->first();

        if ($realizationBesi) {
            MaterialVarianceValidation::updateOrCreate(
                ['material_realization_id' => $realizationBesi->id],
                [
                    'delivery_order_id' => $do1->id,
                    'invoice_id' => $inv1->id,
                    'pengawas_id' => $pengawasUser?->id,
                    'pengawas_validated_at' => Carbon::now()->subDays(7),
                    'pengawas_notes' => 'Fisik besi beton telah diverifikasi di lapangan.',
                    'purchasing_id' => $purchasingUser?->id,
                    'purchasing_validated_at' => Carbon::now()->subDays(4),
                    'purchasing_notes' => 'Harga dan kuantiti sesuai surat jalan dan faktur pajak.',
                    'status' => 'fully_validated',
                ]
            );
        }

        // 5. Equipment Assignment (Fast Input)
        $eqExcavator = EquipmentMaster::where('code', 'EQP-BRT-001')->first();
        $eqGenset = EquipmentMaster::where('code', 'EQP-MSN-001')->first();
        $eqScaffolding = EquipmentMaster::where('code', 'EQP-PLT-004')->first();
        $eqTheodolite = EquipmentMaster::where('code', 'EQP-PLT-005')->first();

        if ($eqExcavator) {
            ProjectEquipment::updateOrCreate(
                ['project_id' => $project->id, 'equipment_master_id' => $eqExcavator->id],
                ['qty' => 1, 'source' => 'sewa', 'condition' => 'layak_pakai', 'added_by' => $pmUser?->id, 'notes' => 'Sewa bulanan PT Sarana Konstruksi']
            );
        }
        if ($eqGenset) {
            ProjectEquipment::updateOrCreate(
                ['project_id' => $project->id, 'equipment_master_id' => $eqGenset->id],
                ['qty' => 2, 'source' => 'milik_sendiri', 'condition' => 'layak_pakai', 'added_by' => $pmUser?->id, 'notes' => 'Standby daya darurat']
            );
        }
        if ($eqScaffolding) {
            ProjectEquipment::updateOrCreate(
                ['project_id' => $project->id, 'equipment_master_id' => $eqScaffolding->id],
                ['qty' => 50, 'source' => 'sewa', 'condition' => 'layak_pakai', 'added_by' => $pengawasUser?->id, 'notes' => 'Pengecoran kolom lantai 1']
            );
        }
        if ($eqTheodolite) {
            ProjectEquipment::updateOrCreate(
                ['project_id' => $project->id, 'equipment_master_id' => $eqTheodolite->id],
                ['qty' => 1, 'source' => 'milik_sendiri', 'condition' => 'baru', 'added_by' => $pengawasUser?->id, 'notes' => 'Alat ukur bowplank & elevasi']
            );
        }
    }

    /**
     * Membuat dummy PDF bukti fisik (Surat Jalan & Invoice) untuk demo jika belum tersedia di disk
     */
    protected function ensureSampleAttachmentsExist(): void
    {
        $dir = storage_path('app/public/attachments');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $doFile = $dir.'/do_sample_surat_jalan.pdf';
        if (! file_exists($doFile)) {
            file_put_contents($doFile, $this->buildMinimalPdf([
                'SURAT JALAN / DELIVERY ORDER (DO)',
                'Nomor: DO/BPS/2026-0891',
                'Tanggal: 19 September 2026',
                'Supplier: PT Baja Prima Steel',
                'Item: Besi Beton Ulir D16 (6.800 Kg)',
                'Status: Diterima & Diverifikasi Lapangan',
            ]));
        }

        $invFile = $dir.'/inv_sample_faktur.pdf';
        if (! file_exists($invFile)) {
            file_put_contents($invFile, $this->buildMinimalPdf([
                'FAKTUR TAGIHAN / INVOICE',
                'Nomor: INV/BPS/26/0442',
                'Tanggal: 22 September 2026',
                'Supplier: PT Baja Prima Steel',
                'Nominal: Rp 132.600.000',
                'Status: Terverifikasi Purchasing',
            ]));
        }
    }

    /**
     * Helper untuk membuat binary PDF standar yang valid
     */
    protected function buildMinimalPdf(array $lines): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';

        $stream = "BT\n/F1 16 Tf\n50 780 Td\n";
        foreach ($lines as $line) {
            $stream .= '('.addcslashes($line, '()\\').") Tj\n0 -25 Td\n";
        }
        $stream .= 'ET';
        $objects[4] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $out = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $obj) {
            $offsets[$id] = strlen($out);
            $out .= "$id 0 obj\n$obj\nendobj\n";
        }
        $xrefOffset = strlen($out);
        $out .= "xref\n0 ".(count($objects) + 1)."\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $out .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $out .= "startxref\n$xrefOffset\n%%EOF\n";

        return $out;
    }
}
