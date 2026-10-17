<?php

namespace App\Services\Rab;

use App\Services\Rab\Contracts\RabProfileInterface;
use App\Services\Rab\Dto\ParsedRow;
use App\Services\Rab\Dto\ParseResult;
use App\Services\Rab\Dto\ValidationIssue;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateV2Parser implements RabProfileInterface
{
    public const PROFILE_ID = 'template_v2';

    public const PROFILE_NAME = 'Template RAB Konstruksi Baku v2';

    public function id(): string
    {
        return self::PROFILE_ID;
    }

    public function name(): string
    {
        return self::PROFILE_NAME;
    }

    public function detect(Spreadsheet $spreadsheet): bool
    {
        return $spreadsheet->sheetNameExists('Info Proyek')
            && $spreadsheet->sheetNameExists('Detail RAB');
    }

    public function parse(string $filePath): ParseResult
    {
        if (! file_exists($filePath)) {
            return $this->createErrorResult('E-TEMPLATE', null, null, "File tidak ditemukan pada path: {$filePath}");
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            return $this->createErrorResult('E-TEMPLATE', null, null, "Format file tidak didukung: .{$extension}. Sistem hanya menerima file .xlsx.");
        }

        $reader = new Xlsx;
        $reader->setReadDataOnly(true);

        try {
            $spreadsheet = $reader->load($filePath);
        } catch (\Throwable $e) {
            return $this->createErrorResult('E-TEMPLATE', null, null, 'File Excel tidak valid atau rusak. Pastikan file berformat .xlsx yang sah.', $e->getMessage());
        }

        $sheetInfo = $spreadsheet->getSheetByName('Info Proyek');
        if (! $sheetInfo) {
            return $this->createErrorResult('E-TEMPLATE', null, null, "Sheet wajib 'Info Proyek' tidak ditemukan.");
        }

        $sheetDetail = $spreadsheet->getSheetByName('Detail RAB');
        if (! $sheetDetail) {
            return $this->createErrorResult('E-TEMPLATE', null, null, "Sheet wajib 'Detail RAB' tidak ditemukan.");
        }

        $issues = [];

        // 1. Parse Detail RAB Headers
        $colMap = $this->resolveHeaders($sheetDetail, $issues);
        if ($colMap === null) {
            // Header resolution failed with E-TEMPLATE
            return $this->buildFinalResult([], [], $issues);
        }

        // 2. Parse Data Rows
        $parsedRows = $this->parseDetailRows($sheetDetail, $colMap, $issues);
        if (empty($parsedRows)) {
            // If no data rows exist, return E-EMPTY as the only error (T1)
            return $this->createErrorResult('E-EMPTY', null, null, 'File Excel tidak memiliki baris data RAB.');
        }

        // 3. Parse and Validate Info Proyek when data rows exist
        $projectData = $this->parseInfoProyek($sheetInfo, $issues);

        return $this->buildFinalResult($projectData, $parsedRows, $issues);
    }

    /**
     * @param  array<int, ValidationIssue>  $issues
     * @return array<string, mixed>
     */
    protected function parseInfoProyek(Worksheet $sheet, array &$issues): array
    {
        $project = [
            'nama_proyek' => null,
            'tipe_prototype' => null,
            'jumlah_lantai' => null,
            'tahun_anggaran' => null,
            'lokasi_kds' => null,
            'tipe_pondasi' => null,
        ];

        $highestRow = min(100, $sheet->getHighestRow());
        $labelRowMap = [];

        for ($r = 1; $r <= $highestRow; $r++) {
            $labelRaw = $sheet->getCell("A{$r}")->getValue();
            if ($labelRaw === null) {
                continue;
            }

            $labelNorm = $this->normalizeString((string) $labelRaw);
            $valRaw = $sheet->getCell("B{$r}")->getValue();
            $val = $valRaw !== null ? trim((string) $valRaw) : '';

            switch ($labelNorm) {
                case 'nama proyek':
                    $project['nama_proyek'] = $val !== '' ? $val : null;
                    $labelRowMap['nama_proyek'] = $r;
                    break;
                case 'tipe prototype':
                    $project['tipe_prototype'] = $val !== '' ? $val : null;
                    $labelRowMap['tipe_prototype'] = $r;
                    break;
                case 'jumlah lantai':
                    $project['jumlah_lantai'] = $val !== '' ? $val : null;
                    $labelRowMap['jumlah_lantai'] = $r;
                    break;
                case 'tahun anggaran':
                    $project['tahun_anggaran'] = $val !== '' ? $val : null;
                    $labelRowMap['tahun_anggaran'] = $r;
                    break;
                case 'lokasi kds':
                    $project['lokasi_kds'] = $val !== '' ? $val : null;
                    $labelRowMap['lokasi_kds'] = $r;
                    break;
                case 'tipe pondasi':
                    $project['tipe_pondasi'] = $val !== '' ? $val : null;
                    $labelRowMap['tipe_pondasi'] = $r;
                    break;
            }
        }

        // Validation: nama_proyek is mandatory
        if (empty($project['nama_proyek'])) {
            $issues[] = new ValidationIssue(
                'ERROR',
                'E-PROJECT',
                $labelRowMap['nama_proyek'] ?? 5,
                'B',
                "Field 'Nama Proyek' wajib diisi pada sheet Info Proyek."
            );
        }

        // Validation: tahun_anggaran is mandatory and 2000..2100
        $tahun = $project['tahun_anggaran'];
        if ($tahun === null || ! is_numeric($tahun) || (int) $tahun < 2000 || (int) $tahun > 2100) {
            $issues[] = new ValidationIssue(
                'ERROR',
                'E-PROJECT',
                $labelRowMap['tahun_anggaran'] ?? 8,
                'B',
                "Field 'Tahun Anggaran' wajib diisi berupa tahun antara 2000 sampai 2100 pada sheet Info Proyek.",
                $tahun
            );
        } else {
            $project['tahun_anggaran'] = (int) $tahun;
        }

        // Validation: jumlah_lantai optional, but 1..100 if provided
        if ($project['jumlah_lantai'] !== null) {
            $lantai = $project['jumlah_lantai'];
            if (! is_numeric($lantai) || (int) $lantai < 1 || (int) $lantai > 100) {
                $issues[] = new ValidationIssue(
                    'ERROR',
                    'E-PROJECT',
                    $labelRowMap['jumlah_lantai'] ?? 7,
                    'B',
                    "Field 'Jumlah Lantai' harus berupa bilangan bulat antara 1 sampai 100.",
                    $lantai
                );
            } else {
                $project['jumlah_lantai'] = (int) $lantai;
            }
        }

        return $project;
    }

    /**
     * @param  array<int, ValidationIssue>  $issues
     * @return array<string, string>|null Map fieldName => columnLetter
     */
    protected function resolveHeaders(Worksheet $sheet, array &$issues): ?array
    {
        $expected = [
            'level' => 'level (1-5)',
            'kode' => 'kode',
            'uraian' => 'uraian pekerjaan / material',
            'volume' => 'volume',
            'satuan' => 'satuan',
            'harga_satuan' => 'harga satuan (rp)',
            'jumlah_harga_file' => 'jumlah harga (rp)',
        ];

        $colMap = [];
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $val = $sheet->getCell("{$colLetter}1")->getValue();
            if ($val === null) {
                continue;
            }

            $norm = $this->normalizeString((string) $val);
            foreach ($expected as $key => $headerLabel) {
                if ($norm === $headerLabel && ! isset($colMap[$key])) {
                    $colMap[$key] = $colLetter;
                }
            }
        }

        $missing = [];
        $headerDisplayNames = [
            'level' => 'Level (1-5)',
            'kode' => 'Kode',
            'uraian' => 'Uraian Pekerjaan / Material',
            'volume' => 'Volume',
            'satuan' => 'Satuan',
            'harga_satuan' => 'Harga Satuan (Rp)',
            'jumlah_harga_file' => 'Jumlah Harga (Rp)',
        ];

        foreach ($expected as $key => $headerLabel) {
            if (! isset($colMap[$key])) {
                $missing[] = $headerDisplayNames[$key];
            }
        }

        if (! empty($missing)) {
            $issues[] = new ValidationIssue(
                'ERROR',
                'E-TEMPLATE',
                1,
                null,
                'Header wajib tidak ditemukan pada sheet Detail RAB baris 1: '.implode(', ', $missing)
            );

            return null;
        }

        return $colMap;
    }

    /**
     * @param  array<string, string>  $colMap
     * @param  array<int, ValidationIssue>  $issues
     * @return array<int, ParsedRow>
     */
    protected function parseDetailRows(Worksheet $sheet, array $colMap, array &$issues): array
    {
        $highestRow = min(1501, $sheet->getHighestRow());
        $rows = [];
        $stack = []; // elements: ['row_no' => int, 'level' => int]
        $sortOrder = 1;
        $hasEncounteredHeading = false;

        for ($r = 2; $r <= $highestRow; $r++) {
            $rawLevel = $this->getCellValue($sheet->getCell($colMap['level'].$r));
            $rawKode = $this->getCellValue($sheet->getCell($colMap['kode'].$r));
            $rawUraian = $this->getCellValue($sheet->getCell($colMap['uraian'].$r));
            $rawVolume = $this->getCellValue($sheet->getCell($colMap['volume'].$r));
            $rawSatuan = $this->getCellValue($sheet->getCell($colMap['satuan'].$r));
            $rawHarga = $this->getCellValue($sheet->getCell($colMap['harga_satuan'].$r));
            $rawJumlah = $this->getCellValue($sheet->getCell($colMap['jumlah_harga_file'].$r));

            // Check if entire row A-F is empty
            $isEmptyRow = ($rawLevel === null || trim((string) $rawLevel) === '')
                && ($rawKode === null || trim((string) $rawKode) === '')
                && ($rawUraian === null || trim((string) $rawUraian) === '')
                && ($rawVolume === null || trim((string) $rawVolume) === '')
                && ($rawSatuan === null || trim((string) $rawSatuan) === '')
                && ($rawHarga === null || trim((string) $rawHarga) === '');

            if ($isEmptyRow) {
                continue;
            }

            // Normalization
            $uraian = $this->cleanText((string) $rawUraian);
            $kode = ($rawKode !== null && trim((string) $rawKode) !== '')
                ? $this->cleanText((string) $rawKode)
                : null;

            // Check W-TOTAL-ROW
            if ($this->isTotalRow($uraian)) {
                $issues[] = new ValidationIssue(
                    'WARNING',
                    'W-TOTAL-ROW',
                    $r,
                    $colMap['uraian'],
                    "Baris rekapitulasi/total ('{$uraian}') diabaikan dan dikeluarkan dari hasil RAB.",
                    $uraian
                );

                continue;
            }

            // Validate Uraian
            if ($uraian === '') {
                $issues[] = new ValidationIssue(
                    'ERROR',
                    'E-URAIAN',
                    $r,
                    $colMap['uraian'],
                    'Uraian pekerjaan atau material tidak boleh kosong.'
                );
            }

            // Validate Level
            $level = null;
            if ($rawLevel !== null && is_numeric($rawLevel) && (int) $rawLevel == (float) $rawLevel) {
                $intL = (int) $rawLevel;
                if ($intL >= 1 && $intL <= 5) {
                    $level = $intL;
                }
            }

            if ($level === null) {
                $issues[] = new ValidationIssue(
                    'ERROR',
                    'E-LEVEL',
                    $r,
                    $colMap['level'],
                    "Level harus berupa bilangan bulat antara 1 sampai 5 (ditemukan: '".trim((string) $rawLevel)."').",
                    $rawLevel
                );
            }

            // Parse numbers: volume, harga_satuan, jumlah_harga_file
            $volParsed = $this->parseNumber($rawVolume, $r, $colMap['volume'], 'Volume', $issues);
            $hargaParsed = $this->parseNumber($rawHarga, $r, $colMap['harga_satuan'], 'Harga Satuan', $issues);
            $jumlahFileParsed = $this->parseNumber($rawJumlah, $r, $colMap['jumlah_harga_file'], 'Jumlah Harga', $issues, false);

            $volume = $volParsed['value'];
            $hargaSatuan = $hargaParsed['value'];
            $jumlahHargaFile = $jumlahFileParsed['value'];

            // Unit normalization
            $unitNorm = UnitNormalizer::normalize($rawSatuan !== null ? (string) $rawSatuan : null);
            $satuan = $unitNorm['canonical'];
            $satuanRaw = $unitNorm['raw'];

            if ($rawSatuan !== null && trim((string) $rawSatuan) !== '' && ! $unitNorm['is_known']) {
                $issues[] = new ValidationIssue(
                    'WARNING',
                    'W-UNIT-UNKNOWN',
                    $r,
                    $colMap['satuan'],
                    "Satuan '{$rawSatuan}' tidak terdapat pada daftar satuan baku.",
                    $rawSatuan
                );
            }

            // Determine item_type & level-specific validations
            $itemType = 'heading';
            if ($level !== null) {
                if ($level >= 1 && $level <= 3) {
                    $itemType = 'heading';
                    $hasEncounteredHeading = true;

                    // Header has values warning
                    if ($volume !== null || $hargaSatuan !== null) {
                        $issues[] = new ValidationIssue(
                            'WARNING',
                            'W-HEADER-HAS-VALUES',
                            $r,
                            $colMap['volume'],
                            "Heading Level {$level} berisi volume atau harga satuan. Nilai tersebut diabaikan."
                        );
                        $volume = null;
                        $hargaSatuan = null;
                        $satuan = null;
                        $satuanRaw = null;
                        $jumlahHargaFile = null;
                    }
                } elseif ($level === 4) {
                    $itemType = 'work_item';
                    if (! $hasEncounteredHeading) {
                        $issues[] = new ValidationIssue(
                            'WARNING',
                            'W-NO-HEADING',
                            $r,
                            $colMap['level'],
                            'Item pekerjaan (Level 4) muncul sebelum ada heading apa pun.'
                        );
                    }

                    if ($volume === null) {
                        $issues[] = new ValidationIssue(
                            'ERROR',
                            'E-MISSING-FIELD',
                            $r,
                            $colMap['volume'],
                            'Volume wajib diisi untuk Level 4.'
                        );
                    }
                    if ($satuan === null || $satuan === '') {
                        $issues[] = new ValidationIssue(
                            'ERROR',
                            'E-MISSING-FIELD',
                            $r,
                            $colMap['satuan'],
                            'Satuan wajib diisi untuk Level 4.'
                        );
                    }
                    if ($hargaSatuan === null) {
                        $issues[] = new ValidationIssue(
                            'ERROR',
                            'E-MISSING-FIELD',
                            $r,
                            $colMap['harga_satuan'],
                            'Harga Satuan wajib diisi untuk Level 4.'
                        );
                    }
                } elseif ($level === 5) {
                    $itemType = 'material';
                    if (! $hasEncounteredHeading) {
                        $issues[] = new ValidationIssue(
                            'WARNING',
                            'W-NO-HEADING',
                            $r,
                            $colMap['level'],
                            'Material (Level 5) muncul sebelum ada heading apa pun.'
                        );
                    }

                    if ($volume === null) {
                        $issues[] = new ValidationIssue(
                            'ERROR',
                            'E-MISSING-FIELD',
                            $r,
                            $colMap['volume'],
                            'Volume wajib diisi untuk Level 5.'
                        );
                    }
                    if ($satuan === null || $satuan === '') {
                        $issues[] = new ValidationIssue(
                            'ERROR',
                            'E-MISSING-FIELD',
                            $r,
                            $colMap['satuan'],
                            'Satuan wajib diisi untuk Level 5.'
                        );
                    }
                    if ($hargaSatuan === null) {
                        $issues[] = new ValidationIssue(
                            'ERROR',
                            'E-MISSING-FIELD',
                            $r,
                            $colMap['harga_satuan'],
                            'Harga Satuan wajib diisi untuk Level 5.'
                        );
                    }
                }
            }

            // Calculate jumlah_harga_calc
            $jumlahHargaCalc = null;
            if ($volume !== null && $hargaSatuan !== null) {
                $jumlahHargaCalc = round($volume * $hargaSatuan, 2);
            }

            // Fallback jumlah_harga_file if empty
            if ($jumlahHargaFile === null && $jumlahHargaCalc !== null) {
                $jumlahHargaFile = $jumlahHargaCalc;
            }

            // Jumlah harga mismatch warning
            if ($jumlahHargaFile !== null && $jumlahHargaCalc !== null) {
                $diff = round($jumlahHargaCalc - $jumlahHargaFile, 2);
                if (abs($diff) > 1.0) {
                    $issues[] = new ValidationIssue(
                        'WARNING',
                        'W-JUMLAH-MISMATCH',
                        $r,
                        $colMap['jumlah_harga_file'],
                        'Jumlah harga file (Rp '.number_format($jumlahHargaFile, 2, ',', '.').') berbeda dengan hasil hitung volume × harga satuan (Rp '.number_format($jumlahHargaCalc, 2, ',', '.').'), selisih: Rp '.number_format($diff, 2, ',', '.').'.',
                        $diff
                    );
                }
            }

            // Hierarchy stack resolution
            $parentRowNo = null;
            if ($level !== null) {
                while (! empty($stack) && end($stack)['level'] >= $level) {
                    array_pop($stack);
                }

                $parent = ! empty($stack) ? end($stack) : null;
                $parentRowNo = $parent ? $parent['row_no'] : null;

                // Validate orphan material (Level 5 parent must be Level 4)
                if ($level === 5) {
                    if ($parent === null || $parent['level'] !== 4) {
                        $issues[] = new ValidationIssue(
                            'ERROR',
                            'E-ORPHAN-MATERIAL',
                            $r,
                            $colMap['level'],
                            'Material (Level 5) harus berada langsung di bawah item pekerjaan (Level 4).'
                        );
                    }
                }

                $stack[] = [
                    'row_no' => $r,
                    'level' => $level,
                ];
            }

            $rows[] = new ParsedRow(
                rowNo: $r,
                sortOrder: $sortOrder++,
                level: $level ?? 1,
                itemType: $itemType,
                kode: $kode,
                uraian: $uraian,
                volume: $volume,
                satuan: $satuan,
                satuanRaw: $satuanRaw,
                hargaSatuan: $hargaSatuan,
                jumlahHargaFile: $jumlahHargaFile,
                jumlahHargaCalc: $jumlahHargaCalc,
                parentRowNo: $parentRowNo,
            );
        }

        // Post-parsing validations
        if (empty($rows)) {
            $issues[] = new ValidationIssue(
                'ERROR',
                'E-EMPTY',
                null,
                null,
                'File Excel tidak memiliki baris data RAB.'
            );

            return [];
        }

        // 1. Check duplicate codes among siblings (same parent)
        $this->validateSiblingDuplicateCodes($rows, $colMap, $issues);

        // 2. Check Level 4 items without materials (I-NO-MATERIAL) & parent sums (I-PARENT-SUM)
        $this->validateLevel4MaterialsAndSums($rows, $colMap, $issues);

        return $rows;
    }

    /**
     * @param  array<int, ParsedRow>  $rows
     * @param  array<string, string>  $colMap
     * @param  array<int, ValidationIssue>  $issues
     */
    protected function validateSiblingDuplicateCodes(array $rows, array $colMap, array &$issues): void
    {
        $siblingsByParent = [];

        foreach ($rows as $row) {
            if ($row->kode === null || trim($row->kode) === '') {
                continue;
            }

            $parentKey = $row->parentRowNo ?? 'root';
            $codeKey = mb_strtolower(trim($row->kode));

            if (isset($siblingsByParent[$parentKey][$codeKey])) {
                $prevRowNo = $siblingsByParent[$parentKey][$codeKey];
                $issues[] = new ValidationIssue(
                    'WARNING',
                    'W-DUP-KODE',
                    $row->rowNo,
                    $colMap['kode'],
                    "Kode '{$row->kode}' duplikat dengan saudara pada baris {$prevRowNo}.",
                    $row->kode
                );
            } else {
                $siblingsByParent[$parentKey][$codeKey] = $row->rowNo;
            }
        }
    }

    /**
     * @param  array<int, ParsedRow>  $rows
     * @param  array<string, string>  $colMap
     * @param  array<int, ValidationIssue>  $issues
     */
    protected function validateLevel4MaterialsAndSums(array $rows, array $colMap, array &$issues): void
    {
        // Map materials by parentRowNo
        $materialsByParent = [];
        foreach ($rows as $row) {
            if ($row->level === 5 && $row->parentRowNo !== null) {
                $materialsByParent[$row->parentRowNo][] = $row;
            }
        }

        foreach ($rows as $row) {
            if ($row->level !== 4) {
                continue;
            }

            $children = $materialsByParent[$row->rowNo] ?? [];

            // I-NO-MATERIAL if no Level 5 children
            if (empty($children)) {
                $issues[] = new ValidationIssue(
                    'INFO',
                    'I-NO-MATERIAL',
                    $row->rowNo,
                    $colMap['uraian'],
                    "Item Level 4 '{$row->uraian}' tidak memiliki rincian material Level 5 (tidak masuk monitoring material)."
                );
            } else {
                // I-PARENT-SUM if item total != sum of material children totals
                $itemTotal = $row->jumlahHargaFile ?? $row->jumlahHargaCalc ?? 0.0;
                $sumChildren = 0.0;
                foreach ($children as $child) {
                    $sumChildren += ($child->jumlahHargaFile ?? $child->jumlahHargaCalc ?? 0.0);
                }

                $diff = round($itemTotal - $sumChildren, 2);
                if (abs($diff) > 1.0) {
                    $issues[] = new ValidationIssue(
                        'INFO',
                        'I-PARENT-SUM',
                        $row->rowNo,
                        $colMap['jumlah_harga_file'],
                        'Total Level 4 (Rp '.number_format($itemTotal, 2, ',', '.').') berbeda dengan total material anaknya (Rp '.number_format($sumChildren, 2, ',', '.').'). Selisih: Rp '.number_format($diff, 2, ',', '.').' (kemungkinan mencakup upah/alat).',
                        $diff
                    );
                }
            }
        }
    }

    /**
     * Parse cell number with flexible decimal / thousand handling.
     *
     * @param  array<int, ValidationIssue>  $issues
     * @return array{value: float|null}
     */
    protected function parseNumber(mixed $rawValue, int $rowNo, string $col, string $fieldName, array &$issues, bool $emitWarnings = true): array
    {
        if ($rawValue === null) {
            return ['value' => null];
        }

        if (is_int($rawValue) || is_float($rawValue)) {
            $num = (float) $rawValue;
            if ($num < 0) {
                $issues[] = new ValidationIssue(
                    'ERROR',
                    'E-NEGATIVE',
                    $rowNo,
                    $col,
                    "Nilai {$fieldName} tidak boleh negatif ({$num}).",
                    $num
                );
            }

            return ['value' => $num];
        }

        $str = trim((string) $rawValue);
        if ($str === '') {
            return ['value' => null];
        }

        // Clean: remove Rp, spaces, NBSP
        $clean = preg_replace('/^Rp\.?\s*/i', '', $str);
        $clean = str_replace(["\xc2\xa0", "\xa0", ' '], '', (string) $clean);

        if ($clean === '') {
            return ['value' => null];
        }

        $hasDot = str_contains($clean, '.');
        $hasComma = str_contains($clean, ',');

        $parsedNum = null;
        $isNegative = str_starts_with($clean, '-');
        $absClean = ltrim($clean, '-');

        if ($hasDot && $hasComma) {
            // Decimal separator is whichever occurs last
            $lastDot = strrpos($absClean, '.');
            $lastComma = strrpos($absClean, ',');

            if ($lastComma > $lastDot) {
                // Comma is decimal separator, dot is thousands separator
                $normalized = str_replace('.', '', $absClean);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                // Dot is decimal separator, comma is thousands separator
                $normalized = str_replace(',', '', $absClean);
            }
        } elseif ($hasComma) {
            // Only comma -> decimal separator
            $normalized = str_replace(',', '.', $absClean);
        } elseif ($hasDot) {
            // Only dot: if matches ^\d{1,3}(\.\d{3})+$ then thousands, otherwise decimal
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $absClean)) {
                $normalized = str_replace('.', '', $absClean);
            } else {
                $normalized = $absClean;
            }
        } else {
            $normalized = $absClean;
        }

        if (is_numeric($normalized)) {
            $parsedNum = (float) $normalized;
            if ($isNegative) {
                $parsedNum = -$parsedNum;
            }

            if ($emitWarnings) {
                $issues[] = new ValidationIssue(
                    'WARNING',
                    'W-TEXT-NUMBER',
                    $rowNo,
                    $col,
                    "Nilai {$fieldName} berupa teks '{$str}' berhasil ditafsirkan menjadi {$parsedNum}.",
                    $parsedNum
                );
            }

            if ($parsedNum < 0) {
                $issues[] = new ValidationIssue(
                    'ERROR',
                    'E-NEGATIVE',
                    $rowNo,
                    $col,
                    "Nilai {$fieldName} tidak boleh negatif ({$parsedNum}).",
                    $parsedNum
                );
            }

            return ['value' => $parsedNum];
        }

        // Could not parse
        $issues[] = new ValidationIssue(
            'ERROR',
            'E-NUMBER',
            $rowNo,
            $col,
            "Nilai '{$str}' pada kolom {$fieldName} tidak dapat dibaca sebagai angka.",
            $str
        );

        return ['value' => null];
    }

    protected function getCellValue(Cell $cell): mixed
    {
        $val = $cell->getValue();
        if (is_string($val) && str_starts_with($val, '=')) {
            $old = $cell->getOldCalculatedValue();

            return ($old !== null && $old !== '') ? $old : null;
        }

        return $val;
    }

    protected function isTotalRow(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        return (bool) preg_match('/^(sub\s*total|jumlah|total|grand\s*total|ppn|pembulatan)\b/iu', $normalized);
    }

    protected function normalizeString(string $val): string
    {
        $cleaned = str_replace(["\xc2\xa0", "\xa0"], ' ', $val);

        return trim((string) preg_replace('/\s+/', ' ', mb_strtolower($cleaned)));
    }

    protected function cleanText(string $val): string
    {
        $cleaned = str_replace(["\xc2\xa0", "\xa0"], ' ', $val);

        return trim((string) preg_replace('/\s+/', ' ', $cleaned));
    }

    /**
     * @param  array<string, mixed>  $project
     * @param  array<int, ParsedRow>  $rows
     * @param  array<int, ValidationIssue>  $issues
     */
    protected function buildFinalResult(array $project, array $rows, array $issues): ParseResult
    {
        // Sort issues by rowNo asc
        usort($issues, function (ValidationIssue $a, ValidationIssue $b) {
            $rA = $a->rowNo ?? 0;
            $rB = $b->rowNo ?? 0;

            return $rA <=> $rB;
        });

        $stats = [
            'rows' => count($rows),
            'headings' => count(array_filter($rows, fn (ParsedRow $r) => in_array($r->level, [1, 2, 3]))),
            'work_items' => count(array_filter($rows, fn (ParsedRow $r) => $r->level === 4)),
            'materials' => count(array_filter($rows, fn (ParsedRow $r) => $r->level === 5)),
            'errors' => count(array_filter($issues, fn (ValidationIssue $i) => $i->severity === 'ERROR')),
            'warnings' => count(array_filter($issues, fn (ValidationIssue $i) => $i->severity === 'WARNING')),
            'infos' => count(array_filter($issues, fn (ValidationIssue $i) => $i->severity === 'INFO')),
        ];

        return new ParseResult($project, $rows, $issues, $stats);
    }

    protected function createErrorResult(string $code, ?int $rowNo, ?string $column, string $message, mixed $value = null): ParseResult
    {
        $issue = new ValidationIssue('ERROR', $code, $rowNo, $column, $message, $value);

        return new ParseResult(
            project: [],
            rows: [],
            issues: [$issue],
            stats: [
                'rows' => 0,
                'headings' => 0,
                'work_items' => 0,
                'materials' => 0,
                'errors' => 1,
                'warnings' => 0,
                'infos' => 0,
            ]
        );
    }
}
