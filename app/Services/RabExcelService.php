<?php

namespace App\Services;

use App\Models\CostRealization;
use App\Models\Material;
use App\Models\MaterialRealization;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Unit;
use App\Services\Rab\UnitNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RabExcelService
{
    /**
     * Generate standard Excel Template for RAB with 2 sheets
     */
    public function generateTemplate(): Spreadsheet
    {
        $v2Path = resource_path('templates/Template_RAB_Konstruksi_Baku_v2.xlsx');
        if (file_exists($v2Path)) {
            $reader = new Xlsx;

            return $reader->load($v2Path);
        }

        $spreadsheet = new Spreadsheet;

        // --- Sheet 1: Info Proyek ---
        $sheetInfo = $spreadsheet->getActiveSheet();
        $sheetInfo->setTitle('Info Proyek');

        $sheetInfo->setCellValue('A1', 'TEMPLATE RESMI IMPORT RAB KONSTRUKSI');
        $sheetInfo->mergeCells('A1:B1');
        $sheetInfo->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheetInfo->setCellValue('A2', 'Silakan isi parameter proyek di bawah ini (kolom B):');
        $sheetInfo->mergeCells('A2:B2');
        $sheetInfo->getStyle('A2')->getFont()->setItalic(true);

        $projectFields = [
            ['Nama Proyek', 'Review Rumah Susun (Prototipe)'],
            ['Tipe Prototype', 'Barak Rembunai'],
            ['Jumlah Lantai', '3'],
            ['Tahun Anggaran', '2026'],
            ['Lokasi KDS', 'Kawasan Rembunai'],
            ['Tipe Pondasi', 'Bored Pile'],
        ];

        $sheetInfo->setCellValue('A4', 'FIELD');
        $sheetInfo->setCellValue('B4', 'NILAI');
        $sheetInfo->getStyle('A4:B4')->getFont()->setBold(true);
        $sheetInfo->getStyle('A4:B4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');

        $row = 5;
        foreach ($projectFields as $f) {
            $sheetInfo->setCellValue("A{$row}", $f[0]);
            $sheetInfo->setCellValue("B{$row}", $f[1]);
            $row++;
        }

        $sheetInfo->getColumnDimension('A')->setWidth(24);
        $sheetInfo->getColumnDimension('B')->setWidth(40);

        // --- Sheet 2: Detail RAB ---
        $sheetDetail = $spreadsheet->createSheet();
        $sheetDetail->setTitle('Detail RAB');

        $headers = [
            'Level (1-5)',
            'Kode',
            'Uraian Pekerjaan / Material',
            'Volume',
            'Satuan',
            'Harga Satuan (Rp)',
            'Jumlah Harga (Rp)',
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheetDetail->setCellValue("{$col}1", $h);
            $sheetDetail->getStyle("{$col}1")->getFont()->setBold(true);
            $sheetDetail->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
            $sheetDetail->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        $sampleData = [
            [1, 'I', 'PEKERJAAN PERSIAPAN STANDAR DAN RK3K KONSTRUKSI', '', '', '', ''],
            [2, 'I.A', 'PEKERJAAN PERSIAPAN', '', '', '', ''],
            [4, '1.0', 'Pengukuran dan Pemasangan Bouwplank', 200, "M'", 64200, 12840000],
            [4, '2.0', 'Pembuatan Direksi Keet & Gudang', 24, 'M2', 1250000, 30000000],
            [2, 'I.B', 'PEKERJAAN RK3K KONSTRUKSI', '', '', '', ''],
            [4, '1.0', 'Penyiapan Rencana K3 Konstruksi & APD', 1, 'Ls', 15000000, 15000000],
            [1, 'II', 'PEKERJAAN STRUKTUR', '', '', '', ''],
            [2, 'II.A', 'PEKERJAAN STRUKTUR LANTAI 1', '', '', '', ''],
            [3, 'A.1', 'PEKERJAAN KOLOM & BALOK LANTAI 1', '', '', '', ''],
            [4, '5.0', 'Kolom K1 Struktur Utama', 21.21, 'M3', 9341400.75, 198128237.32],
            [5, '5.0-1', 'Ready Mix Beton K-300', 21.21, 'M3', 1706697.58, 36202468.96],
            [5, '5.0-2', 'Besi Beton Ulir D16', 6127.04, 'Kg', 19572.16, 119919435.36],
            [5, '5.0-3', 'Bekisting Kolom (Triplek + Kayu)', 182.49, 'M2', 230183.04, 42006333.00],
            [1, 'III', 'PEKERJAAN ARSITEKTUR & SANITASI', '', '', '', ''],
            [2, 'III.A', 'PEKERJAAN KERAMIK & FINISHING', '', '', '', ''],
            [4, '1.0', 'Pemasangan Keramik Lantai 25x20 Kasar Anti-Slip', 85, 'M2', 185000, 15725000],
            [5, '1.0-1', 'Keramik Lantai 25x20 Kasar Anti-Slip', 95, 'M2', 95000, 9025000],
            [5, '1.0-2', 'Semen Portland (PC) 50kg', 35, 'Zak', 78000, 2730000],
            [5, '1.0-3', 'Pasir Pasang Ayak', 4.5, 'M3', 290000, 1305000],
            [2, 'III.B', 'PEKERJAAN SANITASI & PLUMBING', '', '', '', ''],
            [4, '1.0', 'Pemasangan Biofilter Septic Tank 2000L', 1, 'Unit', 18500000, 18500000],
            [5, '1.0-1', 'Biofilter Septic Tank Biotech 2000 Liter', 1, 'Unit', 14500000, 14500000],
            [5, '1.0-2', 'Pipa PVC AW 4" Wavin / Rucika', 6, 'Batang', 340000, 2040000],
        ];

        $rowIdx = 2;
        foreach ($sampleData as $row) {
            $sheetDetail->setCellValue("A{$rowIdx}", $row[0]);
            $sheetDetail->setCellValueExplicit("B{$rowIdx}", (string) $row[1], DataType::TYPE_STRING);
            $sheetDetail->setCellValue("C{$rowIdx}", $row[2]);
            $sheetDetail->setCellValue("D{$rowIdx}", $row[3] !== '' ? (float) $row[3] : '');
            $sheetDetail->setCellValue("E{$rowIdx}", $row[4]);
            $sheetDetail->setCellValue("F{$rowIdx}", $row[5] !== '' ? (float) $row[5] : '');
            $sheetDetail->setCellValue("G{$rowIdx}", $row[6] !== '' ? (float) $row[6] : '');

            // Styling by Level
            if ($row[0] == 1) {
                $sheetDetail->getStyle("A{$rowIdx}:G{$rowIdx}")->getFont()->setBold(true);
                $sheetDetail->getStyle("A{$rowIdx}:G{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            } elseif ($row[0] == 2) {
                $sheetDetail->getStyle("A{$rowIdx}:G{$rowIdx}")->getFont()->setBold(true);
            } elseif ($row[0] == 5) {
                $sheetDetail->getStyle("C{$rowIdx}")->getAlignment()->setIndent(2);
                $sheetDetail->getStyle("A{$rowIdx}:G{$rowIdx}")->getFont()->getColor()->setARGB('FF475569');
            }

            $rowIdx++;
        }

        $sheetDetail->getColumnDimension('A')->setWidth(14);
        $sheetDetail->getColumnDimension('B')->setWidth(12);
        $sheetDetail->getColumnDimension('C')->setWidth(48);
        $sheetDetail->getColumnDimension('D')->setWidth(14);
        $sheetDetail->getColumnDimension('E')->setWidth(10);
        $sheetDetail->getColumnDimension('F')->setWidth(20);
        $sheetDetail->getColumnDimension('G')->setWidth(22);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Preview and Validate Excel File before committing to Database
     */
    public function validateAndPreview(UploadedFile $file): array
    {
        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());

        $result = [
            'valid' => true,
            'project_info' => [],
            'rows' => [],
            'errors' => [],
            'warnings' => [],
            'stats' => [
                'total_rows' => 0,
                'level_1_count' => 0,
                'level_2_count' => 0,
                'level_3_count' => 0,
                'level_4_count' => 0,
                'level_5_count' => 0,
                'total_rab_amount' => 0,
            ],
        ];

        // 1. Read Project Info
        $sheetInfo = $spreadsheet->getSheetByName('Info Proyek');
        if ($sheetInfo) {
            $highestRow = $sheetInfo->getHighestRow();
            for ($r = 4; $r <= $highestRow; $r++) {
                $field = trim((string) $sheetInfo->getCell("A{$r}")->getValue());
                $val = trim((string) $sheetInfo->getCell("B{$r}")->getValue());
                if (! empty($field)) {
                    $result['project_info'][$field] = $val;
                }
            }
        }

        // 2. Read Detail RAB
        $sheetDetail = $spreadsheet->getSheetByName('Detail RAB');
        if (! $sheetDetail) {
            // Fallback to first sheet if only 1 sheet exists
            $sheetDetail = $spreadsheet->getSheet(0);
        }

        $unitLookup = $this->buildUnitLookup(Unit::all());
        $highestRow = $sheetDetail->getHighestRow();

        $activeNode1 = null;
        $activeNode2 = null;
        $activeItem = null;
        $hasCategory = false;
        $defaultCategoryNoted = false;

        for ($r = 2; $r <= $highestRow; $r++) {
            $level = (int) $this->cellValue($sheetDetail->getCell("A{$r}"));
            $name = trim((string) $this->cellValue($sheetDetail->getCell("C{$r}")));

            // Skip empty rows (template v2 berisi formula kolom G hingga baris 1501)
            if (empty($level) && $name === '') {
                continue;
            }

            $code = trim((string) $this->cellValue($sheetDetail->getCell("B{$r}")));
            $volume = $this->numericCell($sheetDetail->getCell("D{$r}"));
            $unitCode = trim((string) $this->cellValue($sheetDetail->getCell("E{$r}")));
            $unitPrice = $this->numericCell($sheetDetail->getCell("F{$r}"));
            $totalPrice = $this->numericCell($sheetDetail->getCell("G{$r}"));

            // Kolom "Jumlah Harga" boleh kosong/formula: hitung dari volume x harga satuan
            if (($totalPrice === null || $totalPrice == 0) && $volume !== null && $unitPrice !== null) {
                $totalPrice = round($volume * $unitPrice, 2);
            }

            $result['stats']['total_rows']++;
            $rowErrors = [];
            $rowNotes = [];
            $autoItem = false;

            // Validation Rules
            if ($level < 1 || $level > 5) {
                $rowErrors[] = "Baris {$r}: Level harus antara 1 sampai 5 (ditemukan '{$level}')";
            }

            if ($name === '') {
                $rowErrors[] = "Baris {$r}: Uraian pekerjaan/material tidak boleh kosong";
            }

            // Hirarki fleksibel: RAB boleh hanya berisi sebagian level (mis. hanya level 4,
            // hanya level 5, atau hanya level 1-3). Level yang "melompat" disesuaikan otomatis.
            if ($level == 1) {
                $result['stats']['level_1_count']++;
                $activeNode1 = $name;
                $activeNode2 = null;
                $activeItem = null;
                $hasCategory = true;
            } elseif ($level == 2) {
                $result['stats']['level_2_count']++;
                if (! $activeNode1) {
                    $rowNotes[] = 'Tanpa Kategori Level 1: dijadikan kategori utama';
                }
                $activeNode2 = $name;
                $activeItem = null;
                $hasCategory = true;
            } elseif ($level == 3) {
                $result['stats']['level_3_count']++;
                if (! $activeNode2) {
                    $rowNotes[] = $activeNode1
                        ? 'Tanpa Sub Kategori Level 2: dipasang langsung di bawah Kategori Level 1'
                        : 'Tanpa Kategori Level 1/2: dijadikan kategori utama';
                }
                $activeItem = null;
                $hasCategory = true;
            } elseif ($level == 4) {
                $result['stats']['level_4_count']++;
                $activeItem = $name;
            } elseif ($level == 5) {
                $result['stats']['level_5_count']++;
                if (! $activeItem) {
                    $autoItem = true;
                    $rowNotes[] = 'Tanpa Item Pekerjaan Level 4: item pekerjaan dibuat otomatis dari material ini';
                }
            }

            if (in_array($level, [4, 5], true) && ! $hasCategory && ! $defaultCategoryNoted) {
                $rowNotes[] = "Tanpa kategori: item dimasukkan ke kategori 'PEKERJAAN UMUM'";
                $defaultCategoryNoted = true;
            }

            if (! empty($rowErrors)) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $rowErrors);
            }

            $result['rows'][] = [
                'excel_row' => $r,
                'level' => $level,
                'code' => $code,
                'name' => $name,
                'volume' => $volume,
                'unit' => $unitCode,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'errors' => $rowErrors,
                'notes' => $rowNotes,
                'auto_item' => $autoItem,
                'as_item' => false,
            ];
        }

        $this->markValuedLeafCategories($result['rows']);

        // Estimasi total & peringatan satuan dihitung dari baris yang benar-benar menjadi item pekerjaan
        foreach ($result['rows'] as $row) {
            $becomesItem = $row['level'] == 4 || $row['auto_item'] || $row['as_item'];
            if ($becomesItem) {
                $result['stats']['total_rab_amount'] += (float) $row['total_price'];
            }

            if (($becomesItem || $row['level'] == 5) && $row['unit'] !== '' && $this->findUnitId($unitLookup, $row['unit']) === null) {
                $result['warnings'][] = "Baris {$row['excel_row']}: Satuan '{$row['unit']}' belum terdaftar di master units (akan otomatis dibuat)";
            }

            foreach ($row['notes'] as $note) {
                $result['warnings'][] = "Baris {$row['excel_row']}: {$note}";
            }
        }

        $result['warnings'] = array_values(array_unique($result['warnings']));

        return $result;
    }

    /**
     * Kategori (level 1-3) tanpa baris turunan yang membawa nilai (jumlah harga) dicatat
     * sebagai item pekerjaan, agar RAB ringkas (hanya level 1/2/3) tetap memiliki anggaran.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function markValuedLeafCategories(array &$rows): void
    {
        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            $level = (int) $rows[$i]['level'];
            if ($level < 1 || $level > 3) {
                continue;
            }

            $hasChildren = $i + 1 < $count && (int) $rows[$i + 1]['level'] > $level;
            if (! $hasChildren && (float) ($rows[$i]['total_price'] ?? 0) > 0) {
                $rows[$i]['as_item'] = true;
                $rows[$i]['notes'][] = 'Kategori tanpa rincian: nilainya dicatat sebagai item pekerjaan';
            }
        }
    }

    /**
     * Import validated data into database
     */
    public function import(array $previewData, ?int $targetProjectId = null, ?int $userId = null): Project
    {
        return DB::transaction(function () use ($previewData, $targetProjectId, $userId) {
            $unitLookup = $this->buildUnitLookup(Unit::all());
            $materialCache = [];

            // 1. Resolve or Create Project
            if ($targetProjectId) {
                $project = Project::findOrFail($targetProjectId);
            } else {
                $info = $previewData['project_info'] ?? [];
                $projectName = $info['Nama Proyek'] ?? ('Proyek Import '.date('d-m-Y H:i'));
                $project = Project::create([
                    'name' => $projectName,
                    'prototype_type' => $info['Tipe Prototype'] ?? null,
                    'floor_count' => (int) ($info['Jumlah Lantai'] ?? 1),
                    'budget_year' => $info['Tahun Anggaran'] ?? (string) date('Y'),
                    'location_kds' => $info['Lokasi KDS'] ?? null,
                    'foundation_type' => $info['Tipe Pondasi'] ?? null,
                    'status' => 'active',
                    'created_by' => $userId,
                ]);
            }

            $currentL1 = null;
            $currentL2 = null;
            $currentL3 = null;
            $currentL4 = null;

            $sortOrder = 1;

            // Node terdalam yang aktif; bila RAB tidak punya kategori sama sekali, buat default
            $resolveTargetNode = function () use (&$currentL1, &$currentL2, &$currentL3, &$sortOrder, $project): RabNode {
                $targetNode = $currentL3 ?: ($currentL2 ?: $currentL1);
                if (! $targetNode) {
                    $targetNode = $currentL1 = RabNode::create([
                        'project_id' => $project->id,
                        'parent_id' => null,
                        'level' => 1,
                        'code' => 'I',
                        'name' => 'PEKERJAAN UMUM',
                        'sort_order' => $sortOrder++,
                    ]);
                }

                return $targetNode;
            };

            // Tanpa event model: subtotal, cost realization, dan baseline material
            // dihitung sekali secara massal setelah seluruh baris tersimpan.
            $createItem = function (RabNode $node, array $attributes) use (&$sortOrder): RabItem {
                return RabItem::createQuietly($attributes + [
                    'rab_node_id' => $node->id,
                    'is_composite' => false,
                    'sort_order' => $sortOrder++,
                ]);
            };

            foreach ($previewData['rows'] as $row) {
                $level = $row['level'];
                $code = $row['code'];
                $name = $row['name'];
                $volume = (float) ($row['volume'] ?? 0);
                $unitPrice = (float) ($row['unit_price'] ?? 0);
                $totalPrice = (float) ($row['total_price'] ?? 0);
                if ($totalPrice == 0 && $volume != 0 && $unitPrice != 0) {
                    $totalPrice = round($volume * $unitPrice, 2);
                }

                // Satuan hanya relevan untuk baris yang menjadi item (level 4/5 atau kategori bernilai)
                $asItem = ! empty($row['as_item']);
                $unitId = ($level >= 4 || $asItem) ? $this->resolveUnitId($unitLookup, (string) ($row['unit'] ?? '')) : null;

                if ($level == 1) {
                    $currentL1 = RabNode::create([
                        'project_id' => $project->id,
                        'parent_id' => null,
                        'level' => 1,
                        'code' => $code ?: 'KATEGORI',
                        'name' => $name,
                        'sort_order' => $sortOrder++,
                    ]);
                    $currentL2 = null;
                    $currentL3 = null;
                    $currentL4 = null;
                } elseif ($level == 2) {
                    // Tanpa level 1 sebelumnya: menjadi kategori utama (parent null)
                    $currentL2 = RabNode::create([
                        'project_id' => $project->id,
                        'parent_id' => $currentL1?->id,
                        'level' => 2,
                        'code' => $code ?: 'SUB',
                        'name' => $name,
                        'sort_order' => $sortOrder++,
                    ]);
                    $currentL3 = null;
                    $currentL4 = null;
                } elseif ($level == 3) {
                    // Pasang ke induk terdekat yang ada (level 2, atau langsung level 1)
                    $currentL3 = RabNode::create([
                        'project_id' => $project->id,
                        'parent_id' => ($currentL2 ?: $currentL1)?->id,
                        'level' => 3,
                        'code' => $code ?: 'SUBSUB',
                        'name' => $name,
                        'sort_order' => $sortOrder++,
                    ]);
                    $currentL4 = null;
                } elseif ($level == 4) {
                    $currentL4 = $createItem($resolveTargetNode(), [
                        'item_no' => $code ?: ($sortOrder.'.0'),
                        'name' => $name,
                        'volume' => $volume,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                } elseif ($level == 5) {
                    // Material tanpa item pekerjaan: buat item otomatis dari baris material ini
                    $parentItem = $currentL4 ?: $createItem($resolveTargetNode(), [
                        'item_no' => $code ?: ($sortOrder.'.0'),
                        'name' => $name,
                        'volume' => $volume,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);

                    // Find or create material in master (cache per nama agar tidak query berulang)
                    $material = $materialCache[$name] ??= Material::firstOrCreate(
                        ['name' => $name],
                        [
                            'code' => $this->generateImportedMaterialCode($name),
                            'category' => 'bahan_dasar',
                            'default_unit_id' => $unitId,
                            'standard_price' => $unitPrice,
                            'is_active' => true,
                        ]
                    );

                    RabItemMaterial::createQuietly([
                        'rab_item_id' => $parentItem->id,
                        'material_id' => $material->id,
                        'volume' => $volume,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'input_by' => $userId,
                    ]);

                    if (! $parentItem->is_composite) {
                        $parentItem->is_composite = true;
                        $parentItem->saveQuietly();
                    }
                }

                // Kategori tanpa rincian yang membawa nilai: dicatat sebagai item pekerjaan
                if ($asItem && $level >= 1 && $level <= 3) {
                    $node = match ($level) {
                        1 => $currentL1,
                        2 => $currentL2,
                        default => $currentL3,
                    };

                    $createItem($node, [
                        'item_no' => $code ?: '1.0',
                        'name' => $name,
                        'volume' => $volume > 0 ? $volume : 1,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice > 0 ? $unitPrice : $totalPrice,
                        'total_price' => $totalPrice,
                    ]);
                }
            }

            // Recalculate all subtotals
            $project->recalculateAllSubtotals();

            // Sinkronkan anggaran cost realization untuk seluruh item RAB proyek
            CostRealization::syncBudgetsForProject($project->id);

            // Recalculate material baselines
            MaterialRealization::recalculateAllForProject($project->id);

            return $project;
        });
    }

    /**
     * Ambil nilai sel; sel formula dievaluasi, error formula dianggap kosong.
     */
    private function cellValue(Cell $cell): mixed
    {
        $value = $cell->getValue();

        if (is_string($value) && str_starts_with($value, '=')) {
            try {
                $value = $cell->getCalculatedValue();
            } catch (\Throwable) {
                $value = $cell->getOldCalculatedValue();
            }

            if (is_string($value) && str_starts_with($value, '#')) {
                return null;
            }
        }

        return $value;
    }

    /**
     * Ambil nilai numerik sel (mendukung formula & teks berformat ribuan "1,250,000").
     */
    private function numericCell(Cell $cell): ?float
    {
        $value = $this->cellValue($cell);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $clean = str_replace([',', ' '], '', trim((string) $value));

        return is_numeric($clean) ? (float) $clean : null;
    }

    /**
     * Index master satuan berdasarkan kode & nama (case-insensitive) agar "LS"/"Ls" atau
     * "Buah"/"Bh" tidak menghasilkan satuan ganda.
     *
     * @return array<string, Unit>
     */
    private function buildUnitLookup(Collection $units): array
    {
        $lookup = [];
        foreach ($units as $unit) {
            $lookup[mb_strtolower(trim($unit->code))] ??= $unit;
        }
        foreach ($units as $unit) {
            $lookup[mb_strtolower(trim($unit->name))] ??= $unit;
        }

        return $lookup;
    }

    /**
     * @param  array<string, Unit>  $lookup
     */
    private function findUnitId(array $lookup, string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $canonical = UnitNormalizer::normalize($raw)['canonical'] ?? $raw;

        return ($lookup[mb_strtolower($raw)] ?? $lookup[mb_strtolower($canonical)] ?? null)?->id;
    }

    /**
     * Cari satuan di master, atau daftarkan otomatis bila belum ada (default "Ls").
     *
     * @param  array<string, Unit>  $lookup
     */
    private function resolveUnitId(array &$lookup, string $raw): int
    {
        $raw = trim($raw) !== '' ? trim($raw) : 'Ls';

        $unitId = $this->findUnitId($lookup, $raw);
        if ($unitId !== null) {
            return $unitId;
        }

        $canonical = UnitNormalizer::normalize($raw)['canonical'] ?? $raw;
        $unit = Unit::create([
            'code' => $canonical,
            'name' => $canonical,
            'group' => 'satuan_hitung',
            'is_base_unit' => false,
        ]);

        $lookup[mb_strtolower($raw)] = $unit;
        $lookup[mb_strtolower($canonical)] = $unit;

        return $unit->id;
    }

    private function generateImportedMaterialCode(string $name): string
    {
        $base = 'MAT-IMP-'.strtoupper(substr(md5($name), 0, 6));
        $code = $base;
        $suffix = 1;

        // Kode unik: hindari bentrok hash dengan material lain yang sudah ada
        while (Material::where('code', $code)->exists()) {
            $code = $base.'-'.$suffix++;
        }

        return $code;
    }

    /**
     * Export complete project RAB to Excel matching client format
     */
    public function export(Project $project): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        // Sheet 1: Info Proyek
        $sheetInfo = $spreadsheet->getActiveSheet();
        $sheetInfo->setTitle('Info Proyek');

        $sheetInfo->setCellValue('A1', 'BILL OF QUANTITY (BQ) / RENCANA ANGGARAN BIAYA');
        $sheetInfo->mergeCells('A1:B1');
        $sheetInfo->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $fields = [
            'PEKERJAAN' => $project->name,
            'TIPE PROTOTYPE' => $project->prototype_type ?: '-',
            'JUMLAH LANTAI' => ($project->floor_count ?: 1).' LANTAI',
            'TAHUN ANGGARAN' => $project->budget_year ?: '-',
            'LOKASI KDS' => $project->location_kds ?: '-',
            'TYPE PONDASI' => $project->foundation_type ?: '-',
            'TOTAL ANGGARAN' => 'Rp '.number_format($project->total_rab, 2, ',', '.'),
        ];

        $r = 3;
        foreach ($fields as $label => $val) {
            $sheetInfo->setCellValue("A{$r}", $label);
            $sheetInfo->setCellValue("B{$r}", $val);
            $sheetInfo->getStyle("A{$r}")->getFont()->setBold(true);
            $r++;
        }
        $sheetInfo->getColumnDimension('A')->setWidth(20);
        $sheetInfo->getColumnDimension('B')->setWidth(45);

        // Sheet 2: Detail RAB
        $sheetDetail = $spreadsheet->createSheet();
        $sheetDetail->setTitle('Detail RAB');

        $headers = [
            'NO.',
            'URAIAN PEKERJAAN',
            'VOL.',
            'SAT.',
            'HARGA SATUAN (Rp.)',
            'JUMLAH HARGA (Rp.)',
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheetDetail->setCellValue("{$col}1", $h);
            $sheetDetail->getStyle("{$col}1")->getFont()->setBold(true);
            $sheetDetail->getStyle("{$col}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
            $sheetDetail->getStyle("{$col}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        $rowIdx = 2;
        $roots = $project->rootRabNodes()
            ->with(RabNode::treeRelations(['unit', 'materials.material', 'materials.unit']))
            ->get();

        foreach ($roots as $node1) {
            // Level 1
            $sheetDetail->setCellValueExplicit("A{$rowIdx}", (string) $node1->code, DataType::TYPE_STRING);
            $sheetDetail->setCellValue("B{$rowIdx}", $node1->name);
            $sheetDetail->setCellValue("F{$rowIdx}", (float) $node1->subtotal_cache);
            $sheetDetail->getStyle("A{$rowIdx}:F{$rowIdx}")->getFont()->setBold(true);
            $sheetDetail->getStyle("A{$rowIdx}:F{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
            $rowIdx++;

            // Items directly under Level 1
            foreach ($node1->rabItems as $item) {
                $rowIdx = $this->writeItemRow($sheetDetail, $item, $rowIdx);
            }

            // Level 2 Sub-categories
            foreach ($node1->children as $node2) {
                $sheetDetail->setCellValueExplicit("A{$rowIdx}", (string) $node2->code, DataType::TYPE_STRING);
                $sheetDetail->setCellValue("B{$rowIdx}", $node2->name);
                $sheetDetail->setCellValue("F{$rowIdx}", (float) $node2->subtotal_cache);
                $sheetDetail->getStyle("A{$rowIdx}:F{$rowIdx}")->getFont()->setBold(true);
                $rowIdx++;

                foreach ($node2->rabItems as $item) {
                    $rowIdx = $this->writeItemRow($sheetDetail, $item, $rowIdx);
                }

                // Level 3 Sub-Sub categories
                foreach ($node2->children as $node3) {
                    $sheetDetail->setCellValueExplicit("A{$rowIdx}", (string) $node3->code, DataType::TYPE_STRING);
                    $sheetDetail->setCellValue("B{$rowIdx}", $node3->name);
                    $sheetDetail->setCellValue("F{$rowIdx}", (float) $node3->subtotal_cache);
                    $sheetDetail->getStyle("A{$rowIdx}:F{$rowIdx}")->getFont()->setBold(true);
                    $rowIdx++;

                    foreach ($node3->rabItems as $item) {
                        $rowIdx = $this->writeItemRow($sheetDetail, $item, $rowIdx);
                    }
                }
            }
        }

        $sheetDetail->getColumnDimension('A')->setWidth(10);
        $sheetDetail->getColumnDimension('B')->setWidth(52);
        $sheetDetail->getColumnDimension('C')->setWidth(14);
        $sheetDetail->getColumnDimension('D')->setWidth(10);
        $sheetDetail->getColumnDimension('E')->setWidth(20);
        $sheetDetail->getColumnDimension('F')->setWidth(22);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function writeItemRow($sheet, RabItem $item, int $rowIdx): int
    {
        $sheet->setCellValueExplicit("A{$rowIdx}", (string) $item->item_no, DataType::TYPE_STRING);
        $sheet->setCellValue("B{$rowIdx}", $item->name);
        $sheet->setCellValue("C{$rowIdx}", (float) $item->volume);
        $sheet->setCellValue("D{$rowIdx}", $item->unit?->code ?? '');
        $sheet->setCellValue("E{$rowIdx}", (float) $item->unit_price);
        $sheet->setCellValue("F{$rowIdx}", (float) $item->total_price);
        $rowIdx++;

        // If composite, write Level 5 breakdowns
        if ($item->is_composite) {
            foreach ($item->materials as $mat) {
                $sheet->setCellValue("A{$rowIdx}", '-');
                $sheet->setCellValue("B{$rowIdx}", '   - '.($mat->material?->name ?? 'Material'));
                $sheet->setCellValue("C{$rowIdx}", (float) $mat->volume);
                $sheet->setCellValue("D{$rowIdx}", $mat->unit?->code ?? '');
                $sheet->setCellValue("E{$rowIdx}", (float) $mat->unit_price);
                $sheet->setCellValue("F{$rowIdx}", (float) $mat->total_price);
                $sheet->getStyle("A{$rowIdx}:F{$rowIdx}")->getFont()->getColor()->setARGB('FF64748B');
                $rowIdx++;
            }
        }

        return $rowIdx;
    }
}
