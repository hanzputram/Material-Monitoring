<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Project;
use App\Models\RabItem;
use App\Models\RabItemMaterial;
use App\Models\RabNode;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RabExcelService
{
    /**
     * Generate standard Excel Template for RAB with 2 sheets
     */
    public function generateTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

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
            $sheetDetail->setCellValueExplicit("B{$rowIdx}", (string)$row[1], DataType::TYPE_STRING);
            $sheetDetail->setCellValue("C{$rowIdx}", $row[2]);
            $sheetDetail->setCellValue("D{$rowIdx}", $row[3] !== '' ? (float)$row[3] : '');
            $sheetDetail->setCellValue("E{$rowIdx}", $row[4]);
            $sheetDetail->setCellValue("F{$rowIdx}", $row[5] !== '' ? (float)$row[5] : '');
            $sheetDetail->setCellValue("G{$rowIdx}", $row[6] !== '' ? (float)$row[6] : '');

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
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
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
                $field = trim((string)$sheetInfo->getCell("A{$r}")->getValue());
                $val = trim((string)$sheetInfo->getCell("B{$r}")->getValue());
                if (!empty($field)) {
                    $result['project_info'][$field] = $val;
                }
            }
        }

        // 2. Read Detail RAB
        $sheetDetail = $spreadsheet->getSheetByName('Detail RAB');
        if (!$sheetDetail) {
            // Fallback to first sheet if only 1 sheet exists
            $sheetDetail = $spreadsheet->getSheet(0);
        }

        $unitsMap = Unit::all()->pluck('id', 'code')->toArray();
        $highestRow = $sheetDetail->getHighestRow();

        $activeNode1 = null;
        $activeNode2 = null;
        $activeNode3 = null;
        $activeItem = null;

        for ($r = 2; $r <= $highestRow; $r++) {
            $level = (int)$sheetDetail->getCell("A{$r}")->getValue();
            $code = trim((string)$sheetDetail->getCell("B{$r}")->getValue());
            $name = trim((string)$sheetDetail->getCell("C{$r}")->getValue());
            $volume = $sheetDetail->getCell("D{$r}")->getValue();
            $unitCode = trim((string)$sheetDetail->getCell("E{$r}")->getValue());
            $unitPrice = $sheetDetail->getCell("F{$r}")->getValue();
            $totalPrice = $sheetDetail->getCell("G{$r}")->getValue();

            // Skip empty rows
            if (empty($level) && empty($name)) {
                continue;
            }

            $result['stats']['total_rows']++;
            $rowErrors = [];

            // Validation Rules
            if ($level < 1 || $level > 5) {
                $rowErrors[] = "Baris {$r}: Level harus antara 1 sampai 5 (ditemukan '{$level}')";
            }

            if (empty($name)) {
                $rowErrors[] = "Baris {$r}: Uraian pekerjaan/material tidak boleh kosong";
            }

            // Hierarchy validation
            if ($level == 1) {
                $result['stats']['level_1_count']++;
                $activeNode1 = $name;
                $activeNode2 = null;
                $activeNode3 = null;
                $activeItem = null;
            } elseif ($level == 2) {
                $result['stats']['level_2_count']++;
                if (!$activeNode1) {
                    $rowErrors[] = "Baris {$r}: Sub Kategori (Level 2) muncul sebelum ada Kategori (Level 1)";
                }
                $activeNode2 = $name;
                $activeNode3 = null;
                $activeItem = null;
            } elseif ($level == 3) {
                $result['stats']['level_3_count']++;
                if (!$activeNode2) {
                    $rowErrors[] = "Baris {$r}: Sub-Sub Kategori (Level 3) muncul sebelum ada Sub Kategori (Level 2)";
                }
                $activeNode3 = $name;
                $activeItem = null;
            } elseif ($level == 4) {
                $result['stats']['level_4_count']++;
                $activeItem = $name;
                $numericVol = (float)str_replace(',', '', (string)$volume);
                $numericPrice = (float)str_replace(',', '', (string)$unitPrice);
                $calcTotal = $numericVol * $numericPrice;
                $numericTotal = (float)str_replace(',', '', (string)$totalPrice);

                if ($numericTotal > 0) {
                    $result['stats']['total_rab_amount'] += $numericTotal;
                } else {
                    $result['stats']['total_rab_amount'] += $calcTotal;
                }

                if (!empty($unitCode) && !isset($unitsMap[$unitCode])) {
                    $result['warnings'][] = "Baris {$r}: Satuan '{$unitCode}' belum terdaftar di master units (akan otomatis dibuat)";
                }
            } elseif ($level == 5) {
                $result['stats']['level_5_count']++;
                if (!$activeItem) {
                    $rowErrors[] = "Baris {$r}: Material Breakdown (Level 5) harus berada di bawah Item Pekerjaan (Level 4)";
                }
            }

            if (!empty($rowErrors)) {
                $result['valid'] = false;
                $result['errors'] = array_merge($result['errors'], $rowErrors);
            }

            $result['rows'][] = [
                'excel_row' => $r,
                'level' => $level,
                'code' => $code,
                'name' => $name,
                'volume' => $volume !== null && $volume !== '' ? (float)str_replace(',', '', (string)$volume) : null,
                'unit' => $unitCode,
                'unit_price' => $unitPrice !== null && $unitPrice !== '' ? (float)str_replace(',', '', (string)$unitPrice) : null,
                'total_price' => $totalPrice !== null && $totalPrice !== '' ? (float)str_replace(',', '', (string)$totalPrice) : null,
                'errors' => $rowErrors,
            ];
        }

        return $result;
    }

    /**
     * Import validated data into database
     */
    public function import(array $previewData, ?int $targetProjectId = null, ?int $userId = null): Project
    {
        return DB::transaction(function () use ($previewData, $targetProjectId, $userId) {
            $unitsMap = Unit::all()->keyBy('code');

            // 1. Resolve or Create Project
            if ($targetProjectId) {
                $project = Project::findOrFail($targetProjectId);
            } else {
                $info = $previewData['project_info'] ?? [];
                $projectName = $info['Nama Proyek'] ?? ('Proyek Import ' . date('d-m-Y H:i'));
                $project = Project::create([
                    'name' => $projectName,
                    'prototype_type' => $info['Tipe Prototype'] ?? null,
                    'floor_count' => (int)($info['Jumlah Lantai'] ?? 1),
                    'budget_year' => $info['Tahun Anggaran'] ?? (string)date('Y'),
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

            foreach ($previewData['rows'] as $row) {
                $level = $row['level'];
                $code = $row['code'];
                $name = $row['name'];
                $volume = $row['volume'] ?? 0;
                $unitCode = $row['unit'] ?? 'Ls';
                $unitPrice = $row['unit_price'] ?? 0;
                $totalPrice = $row['total_price'] ?? ($volume * $unitPrice);

                // Auto register unit if missing
                if (!empty($unitCode) && !isset($unitsMap[$unitCode])) {
                    $newUnit = Unit::create([
                        'code' => $unitCode,
                        'name' => $unitCode,
                        'group' => 'satuan_hitung',
                        'is_base_unit' => false,
                    ]);
                    $unitsMap[$unitCode] = $newUnit;
                }
                $unitId = $unitsMap[$unitCode]->id ?? 1;

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
                    $currentL3 = RabNode::create([
                        'project_id' => $project->id,
                        'parent_id' => $currentL2?->id,
                        'level' => 3,
                        'code' => $code ?: 'SUBSUB',
                        'name' => $name,
                        'sort_order' => $sortOrder++,
                    ]);
                    $currentL4 = null;
                } elseif ($level == 4) {
                    // Deepest active node
                    $targetNode = $currentL3 ?: ($currentL2 ?: $currentL1);
                    if (!$targetNode) {
                        // Create default category if missing
                        $targetNode = RabNode::create([
                            'project_id' => $project->id,
                            'parent_id' => null,
                            'level' => 1,
                            'code' => 'I',
                            'name' => 'PEKERJAAN UMUM',
                            'sort_order' => $sortOrder++,
                        ]);
                        $currentL1 = $targetNode;
                    }

                    $currentL4 = RabItem::create([
                        'rab_node_id' => $targetNode->id,
                        'item_no' => $code ?: ($sortOrder . '.0'),
                        'name' => $name,
                        'volume' => $volume,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'is_composite' => false,
                        'sort_order' => $sortOrder++,
                    ]);
                } elseif ($level == 5 && $currentL4) {
                    // Find or create material in master
                    $material = Material::firstOrCreate(
                        ['name' => $name],
                        [
                            'code' => 'MAT-IMP-' . strtoupper(substr(md5($name), 0, 6)),
                            'category' => 'bahan_dasar',
                            'default_unit_id' => $unitId,
                            'standard_price' => $unitPrice,
                            'is_active' => true,
                        ]
                    );

                    RabItemMaterial::create([
                        'rab_item_id' => $currentL4->id,
                        'material_id' => $material->id,
                        'volume' => $volume,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'input_by' => $userId,
                    ]);

                    $currentL4->is_composite = true;
                    $currentL4->saveQuietly();
                }
            }

            // Recalculate all subtotals
            $project->recalculateAllSubtotals();

            // Recalculate material baselines
            MaterialRealization::recalculateAllForProject($project->id);

            return $project;
        });
    }

    /**
     * Export complete project RAB to Excel matching client format
     */
    public function export(Project $project): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Info Proyek
        $sheetInfo = $spreadsheet->getActiveSheet();
        $sheetInfo->setTitle('Info Proyek');

        $sheetInfo->setCellValue('A1', 'BILL OF QUANTITY (BQ) / RENCANA ANGGARAN BIAYA');
        $sheetInfo->mergeCells('A1:B1');
        $sheetInfo->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $fields = [
            'PEKERJAAN' => $project->name,
            'TIPE PROTOTYPE' => $project->prototype_type ?: '-',
            'JUMLAH LANTAI' => ($project->floor_count ?: 1) . ' LANTAI',
            'TAHUN ANGGARAN' => $project->budget_year ?: '-',
            'LOKASI KDS' => $project->location_kds ?: '-',
            'TYPE PONDASI' => $project->foundation_type ?: '-',
            'TOTAL ANGGARAN' => 'Rp ' . number_format($project->total_rab, 2, ',', '.'),
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
        $roots = $project->rootRabNodes;

        foreach ($roots as $node1) {
            // Level 1
            $sheetDetail->setCellValueExplicit("A{$rowIdx}", (string)$node1->code, DataType::TYPE_STRING);
            $sheetDetail->setCellValue("B{$rowIdx}", $node1->name);
            $sheetDetail->setCellValue("F{$rowIdx}", (float)$node1->subtotal_cache);
            $sheetDetail->getStyle("A{$rowIdx}:F{$rowIdx}")->getFont()->setBold(true);
            $sheetDetail->getStyle("A{$rowIdx}:F{$rowIdx}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
            $rowIdx++;

            // Items directly under Level 1
            foreach ($node1->rabItems as $item) {
                $rowIdx = $this->writeItemRow($sheetDetail, $item, $rowIdx);
            }

            // Level 2 Sub-categories
            foreach ($node1->children as $node2) {
                $sheetDetail->setCellValueExplicit("A{$rowIdx}", (string)$node2->code, DataType::TYPE_STRING);
                $sheetDetail->setCellValue("B{$rowIdx}", $node2->name);
                $sheetDetail->setCellValue("F{$rowIdx}", (float)$node2->subtotal_cache);
                $sheetDetail->getStyle("A{$rowIdx}:F{$rowIdx}")->getFont()->setBold(true);
                $rowIdx++;

                foreach ($node2->rabItems as $item) {
                    $rowIdx = $this->writeItemRow($sheetDetail, $item, $rowIdx);
                }

                // Level 3 Sub-Sub categories
                foreach ($node2->children as $node3) {
                    $sheetDetail->setCellValueExplicit("A{$rowIdx}", (string)$node3->code, DataType::TYPE_STRING);
                    $sheetDetail->setCellValue("B{$rowIdx}", $node3->name);
                    $sheetDetail->setCellValue("F{$rowIdx}", (float)$node3->subtotal_cache);
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
        $sheet->setCellValueExplicit("A{$rowIdx}", (string)$item->item_no, DataType::TYPE_STRING);
        $sheet->setCellValue("B{$rowIdx}", $item->name);
        $sheet->setCellValue("C{$rowIdx}", (float)$item->volume);
        $sheet->setCellValue("D{$rowIdx}", $item->unit?->code ?? '');
        $sheet->setCellValue("E{$rowIdx}", (float)$item->unit_price);
        $sheet->setCellValue("F{$rowIdx}", (float)$item->total_price);
        $rowIdx++;

        // If composite, write Level 5 breakdowns
        if ($item->is_composite) {
            foreach ($item->materials as $mat) {
                $sheet->setCellValue("A{$rowIdx}", '-');
                $sheet->setCellValue("B{$rowIdx}", '   - ' . ($mat->material?->name ?? 'Material'));
                $sheet->setCellValue("C{$rowIdx}", (float)$mat->volume);
                $sheet->setCellValue("D{$rowIdx}", $mat->unit?->code ?? '');
                $sheet->setCellValue("E{$rowIdx}", (float)$mat->unit_price);
                $sheet->setCellValue("F{$rowIdx}", (float)$mat->total_price);
                $sheet->getStyle("A{$rowIdx}:F{$rowIdx}")->getFont()->getColor()->setARGB('FF64748B');
                $rowIdx++;
            }
        }

        return $rowIdx;
    }
}
