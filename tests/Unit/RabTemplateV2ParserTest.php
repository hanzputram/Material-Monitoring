<?php

namespace Tests\Unit;

use App\Services\Rab\Dto\ValidationIssue;
use App\Services\Rab\TemplateV2Parser;
use App\Services\Rab\UnitNormalizer;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PHPUnit\Framework\TestCase;

class RabTemplateV2ParserTest extends TestCase
{
    protected TemplateV2Parser $parser;

    protected array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TemplateV2Parser;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    protected function createTempXlsx(Spreadsheet $spreadsheet): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'rab_test_').'.xlsx';
        $writer = new XlsxWriter($spreadsheet);
        $writer->save($tmp);
        $this->tempFiles[] = $tmp;

        return $tmp;
    }

    protected function getFixturePath(string $fileName): string
    {
        return dirname(__DIR__, 2).'/tests/fixtures/'.$fileName;
    }

    protected function makeMinimalValidSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        // Info Proyek
        $info = $spreadsheet->getActiveSheet();
        $info->setTitle('Info Proyek');
        $info->setCellValue('A5', 'Nama Proyek');
        $info->setCellValue('B5', 'Proyek Uji Unit');
        $info->setCellValue('A8', 'Tahun Anggaran');
        $info->setCellValue('B8', 2026);

        // Detail RAB
        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Detail RAB');
        $headers = [
            'Level (1-5)',
            'Kode',
            'Uraian Pekerjaan / Material',
            'Volume',
            'Satuan',
            'Harga Satuan (Rp)',
            'Jumlah Harga (Rp)',
        ];

        foreach ($headers as $index => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($index + 1);
            $detail->setCellValue("{$colLetter}1", $h);
        }

        return $spreadsheet;
    }

    /**
     * T1: Template v2 kosong -> E-EMPTY, error = 1
     */
    public function test_t1_empty_template_v2_returns_e_empty(): void
    {
        $fixturePath = $this->getFixturePath('Template_RAB_Konstruksi_Baku_v2.xlsx');
        $this->assertFileExists($fixturePath);

        $result = $this->parser->parse($fixturePath);

        $this->assertSame(1, $result->stats['errors']);
        $this->assertSame(0, $result->stats['rows']);

        $errorIssues = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->severity === 'ERROR'));
        $this->assertCount(1, $errorIssues);
        $this->assertSame('E-EMPTY', $errorIssues[0]->code);
    }

    /**
     * T2: Template_RAB_Konstruksi_Baku_1.xlsx -> 23 baris: 9 heading, 6 work_item, 8 material. Error = 0
     */
    public function test_t2_template_baku_1_has_23_rows_and_zero_errors(): void
    {
        $fixturePath = $this->getFixturePath('Template_RAB_Konstruksi_Baku_1.xlsx');
        $this->assertFileExists($fixturePath);

        $result = $this->parser->parse($fixturePath);

        $this->assertSame(0, $result->stats['errors']);
        $this->assertSame(23, $result->stats['rows']);
        $this->assertSame(9, $result->stats['headings']);
        $this->assertSame(6, $result->stats['work_items']);
        $this->assertSame(8, $result->stats['materials']);
    }

    /**
     * T3: (sama dengan T2) Tepat 4 W-JUMLAH-MISMATCH di baris Excel 11, 12, 13, 14 dengan selisih 2872.59, -3413.29, -28.15, -230.03
     */
    public function test_t3_template_baku_1_has_exactly_four_jumlah_mismatches(): void
    {
        $fixturePath = $this->getFixturePath('Template_RAB_Konstruksi_Baku_1.xlsx');
        $result = $this->parser->parse($fixturePath);

        $mismatches = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'W-JUMLAH-MISMATCH'));

        $this->assertCount(4, $mismatches);

        $expected = [
            11 => 2872.59,
            12 => -3413.29,
            13 => -28.15,
            14 => -230.03,
        ];

        foreach ($mismatches as $issue) {
            $this->assertArrayHasKey($issue->rowNo, $expected);
            $this->assertEqualsWithDelta($expected[$issue->rowNo], (float) $issue->value, 0.01);
        }
    }

    /**
     * T4: (sama dengan T2) Tepat 3 I-NO-MATERIAL di baris 4, 5, 7, dan 2 I-PARENT-SUM di baris 17 dan 22 (total INFO = 5)
     */
    public function test_t4_template_baku_1_has_three_no_material_and_two_parent_sum_infos(): void
    {
        $fixturePath = $this->getFixturePath('Template_RAB_Konstruksi_Baku_1.xlsx');
        $result = $this->parser->parse($fixturePath);

        $infoIssues = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->severity === 'INFO'));
        $this->assertSame(5, $result->stats['infos']);
        $this->assertCount(5, $infoIssues);

        $noMaterials = array_values(array_filter($infoIssues, fn (ValidationIssue $i) => $i->code === 'I-NO-MATERIAL'));
        $this->assertCount(3, $noMaterials);
        $this->assertSame([4, 5, 7], array_map(fn (ValidationIssue $i) => $i->rowNo, $noMaterials));

        $parentSums = array_values(array_filter($infoIssues, fn (ValidationIssue $i) => $i->code === 'I-PARENT-SUM'));
        $this->assertCount(2, $parentSums);
        $this->assertSame([17, 22], array_map(fn (ValidationIssue $i) => $i->rowNo, $parentSums));
    }

    /**
     * T5: (sama dengan T2) Induk benar: baris 12->11, 11->10, 10->9, 9->8, 4->3, 3->2, 17->16
     */
    public function test_t5_template_baku_1_has_correct_hierarchy_parents(): void
    {
        $fixturePath = $this->getFixturePath('Template_RAB_Konstruksi_Baku_1.xlsx');
        $result = $this->parser->parse($fixturePath);

        $rowsByNo = [];
        foreach ($result->rows as $r) {
            $rowsByNo[$r->rowNo] = $r;
        }

        $this->assertSame(11, $rowsByNo[12]->parentRowNo);
        $this->assertSame(10, $rowsByNo[11]->parentRowNo);
        $this->assertSame(9, $rowsByNo[10]->parentRowNo);
        $this->assertSame(8, $rowsByNo[9]->parentRowNo);
        $this->assertSame(3, $rowsByNo[4]->parentRowNo);
        $this->assertSame(2, $rowsByNo[3]->parentRowNo);
        $this->assertSame(16, $rowsByNo[17]->parentRowNo);
    }

    /**
     * T6: Template v2 diisi dengan isi sheet "Contoh Pengisian" -> Error = 0, warning = 0
     */
    public function test_t6_template_v2_filled_with_contoh_pengisian_has_zero_errors_and_zero_warnings(): void
    {
        $reader = new XlsxReader;
        $spreadsheet = $reader->load($this->getFixturePath('Template_RAB_Konstruksi_Baku_v2.xlsx'));

        $cp = $spreadsheet->getSheetByName('Contoh Pengisian');
        $dr = $spreadsheet->getSheetByName('Detail RAB');
        $ip = $spreadsheet->getSheetByName('Info Proyek');

        $ip->setCellValue('B5', 'Review Rumah Susun (Prototipe)');
        $ip->setCellValue('B8', 2026);

        // Copy rows 4..26 from Contoh Pengisian into Detail RAB rows 2..24
        for ($r = 4; $r <= 26; $r++) {
            $destRow = $r - 2;
            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                $val = $cp->getCell($col.$r)->getValue();
                $dr->setCellValue($col.$destRow, $val);
            }
            $vol = $cp->getCell('D'.$r)->getValue();
            $price = $cp->getCell('F'.$r)->getValue();
            if (is_numeric($vol) && is_numeric($price)) {
                $dr->setCellValue('G'.$destRow, round((float) $vol * (float) $price, 2));
            } else {
                $dr->setCellValue('G'.$destRow, null);
            }
        }

        $tmpFile = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmpFile);

        $this->assertSame(0, $result->stats['errors'], 'T6 should have 0 errors');
        $this->assertSame(0, $result->stats['warnings'], 'T6 should have 0 warnings');
        $this->assertSame(23, $result->stats['rows']);
    }

    /**
     * T7: Kode 1.0 berulang di induk berbeda -> Tidak ada W-DUP-KODE
     */
    public function test_t7_same_code_repeated_under_different_parents_has_no_w_dup_kode(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        // Heading 1
        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        // Work item under Heading 1 with kode 1.0
        $detail->setCellValue('A3', 4);
        $detail->setCellValue('B3', '1.0');
        $detail->setCellValue('C3', 'Item Pertama');
        $detail->setCellValue('D3', 10);
        $detail->setCellValue('E3', 'M2');
        $detail->setCellValue('F3', 50000);
        $detail->setCellValue('G3', 500000);

        // Heading 2
        $detail->setCellValue('A4', 1);
        $detail->setCellValue('B4', 'II');
        $detail->setCellValue('C4', 'PEKERJAAN DUA');

        // Work item under Heading 2 with kode 1.0 (different parent!)
        $detail->setCellValue('A5', 4);
        $detail->setCellValue('B5', '1.0');
        $detail->setCellValue('C5', 'Item Kedua');
        $detail->setCellValue('D5', 20);
        $detail->setCellValue('E5', 'M2');
        $detail->setCellValue('F5', 50000);
        $detail->setCellValue('G5', 1000000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $dupCodes = array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'W-DUP-KODE');
        $this->assertEmpty($dupCodes, 'Code 1.0 repeated under different parents should not trigger W-DUP-KODE');
    }

    /**
     * T8: Dua saudara berkode sama -> W-DUP-KODE
     */
    public function test_t8_two_siblings_with_same_code_triggers_w_dup_kode(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        // Heading 1
        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        // Sibling 1
        $detail->setCellValue('A3', 4);
        $detail->setCellValue('B3', '1.0');
        $detail->setCellValue('C3', 'Item Pertama');
        $detail->setCellValue('D3', 10);
        $detail->setCellValue('E3', 'M2');
        $detail->setCellValue('F3', 50000);
        $detail->setCellValue('G3', 500000);

        // Sibling 2 under the same heading with same code 1.0
        $detail->setCellValue('A4', 4);
        $detail->setCellValue('B4', '1.0');
        $detail->setCellValue('C4', 'Item Kedua');
        $detail->setCellValue('D4', 20);
        $detail->setCellValue('E4', 'M2');
        $detail->setCellValue('F3', 50000);
        $detail->setCellValue('G3', 1000000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $dupCodes = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'W-DUP-KODE'));
        $this->assertNotEmpty($dupCodes);
        $this->assertSame('W-DUP-KODE', $dupCodes[0]->code);
        $this->assertSame(4, $dupCodes[0]->rowNo);
    }

    /**
     * T9: Header Volume dihapus -> E-TEMPLATE menyebut Volume
     */
    public function test_t9_missing_volume_header_triggers_e_template_mentioning_volume(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');
        // Clear Volume header in D1
        $detail->setCellValue('D1', '');

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $this->assertSame(1, $result->stats['errors']);
        $error = $result->issues[0];
        $this->assertSame('E-TEMPLATE', $error->code);
        $this->assertStringContainsString('Volume', $error->message);
    }

    /**
     * T10: Urutan kolom diacak, header sama -> Hasil sama dengan T2
     */
    public function test_t10_shuffled_header_columns_produces_identical_parsed_rows(): void
    {
        $fixturePath = $this->getFixturePath('Template_RAB_Konstruksi_Baku_1.xlsx');
        $originalResult = $this->parser->parse($fixturePath);

        // Build a workbook with shuffled columns: [Uraian, Volume, Level, Harga Satuan, Kode, Jumlah Harga, Satuan]
        $spreadsheet = new Spreadsheet;
        $info = $spreadsheet->getActiveSheet();
        $info->setTitle('Info Proyek');
        $info->setCellValue('A5', 'Nama Proyek');
        $info->setCellValue('B5', 'Review Rumah Susun (Prototipe)');
        $info->setCellValue('A8', 'Tahun Anggaran');
        $info->setCellValue('B8', 2026);

        $detail = $spreadsheet->createSheet();
        $detail->setTitle('Detail RAB');

        $shuffledHeaders = [
            'A' => 'Uraian Pekerjaan / Material',
            'B' => 'Volume',
            'C' => 'Level (1-5)',
            'D' => 'Harga Satuan (Rp)',
            'E' => 'Kode',
            'F' => 'Jumlah Harga (Rp)',
            'G' => 'Satuan',
        ];

        foreach ($shuffledHeaders as $col => $title) {
            $detail->setCellValue("{$col}1", $title);
        }

        // Fill data from originalResult
        $rIdx = 2;
        foreach ($originalResult->rows as $r) {
            $detail->setCellValue("A{$rIdx}", $r->uraian);
            $detail->setCellValue("B{$rIdx}", $r->volume);
            $detail->setCellValue("C{$rIdx}", $r->level);
            $detail->setCellValue("D{$rIdx}", $r->hargaSatuan);
            $detail->setCellValue("E{$rIdx}", $r->kode);
            $detail->setCellValue("F{$rIdx}", $r->jumlahHargaFile);
            $detail->setCellValue("G{$rIdx}", $r->satuanRaw);
            $rIdx++;
        }

        $tmp = $this->createTempXlsx($spreadsheet);
        $shuffledResult = $this->parser->parse($tmp);

        $this->assertSame($originalResult->stats['rows'], $shuffledResult->stats['rows']);
        $this->assertSame($originalResult->stats['headings'], $shuffledResult->stats['headings']);
        $this->assertSame($originalResult->stats['work_items'], $shuffledResult->stats['work_items']);
        $this->assertSame($originalResult->stats['materials'], $shuffledResult->stats['materials']);
    }

    /**
     * T11: Volume berupa teks "1.234,56" -> Nilai 1234.56 + W-TEXT-NUMBER
     */
    public function test_t11_text_formatted_number_parsed_correctly_with_warning(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        $detail->setCellValue('A3', 4);
        $detail->setCellValue('B3', '1.0');
        $detail->setCellValue('C3', 'Item Uji Format Teks');
        $detail->setCellValueExplicit('D3', '1.234,56', DataType::TYPE_STRING);
        $detail->setCellValue('E3', 'M2');
        $detail->setCellValue('F3', 1000);
        $detail->setCellValue('G3', 1234560);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $itemRow = $result->rows[1];
        $this->assertSame(1234.56, $itemRow->volume);

        $textWarnings = array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'W-TEXT-NUMBER');
        $this->assertNotEmpty($textWarnings);
    }

    /**
     * T12: Volume berupa teks "10 m3" -> E-NUMBER
     */
    public function test_t12_unparseable_number_text_triggers_e_number(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        $detail->setCellValue('A3', 4);
        $detail->setCellValue('B3', '1.0');
        $detail->setCellValue('C3', 'Item Volume Error');
        $detail->setCellValueExplicit('D3', '10 m3', DataType::TYPE_STRING);
        $detail->setCellValue('E3', 'M3');
        $detail->setCellValue('F3', 100000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $numberErrors = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'E-NUMBER'));
        $this->assertNotEmpty($numberErrors);
        $this->assertSame('E-NUMBER', $numberErrors[0]->code);
        $this->assertSame(3, $numberErrors[0]->rowNo);
    }

    /**
     * T13: Level kosong pada baris berisi data -> E-LEVEL
     */
    public function test_t13_empty_level_on_data_row_triggers_e_level(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        // Row with data in Uraian but empty Level
        $detail->setCellValue('B2', '1.0');
        $detail->setCellValue('C2', 'Pekerjaan Tanpa Level');
        $detail->setCellValue('D2', 10);
        $detail->setCellValue('E2', 'M2');
        $detail->setCellValue('F2', 50000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $levelErrors = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'E-LEVEL'));
        $this->assertNotEmpty($levelErrors);
        $this->assertSame('E-LEVEL', $levelErrors[0]->code);
        $this->assertSame(2, $levelErrors[0]->rowNo);
    }

    /**
     * T14: Level 4 tanpa satuan -> E-MISSING-FIELD
     */
    public function test_t14_level_4_missing_unit_triggers_e_missing_field(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        $detail->setCellValue('A3', 4);
        $detail->setCellValue('B3', '1.0');
        $detail->setCellValue('C3', 'Pekerjaan Tanpa Satuan');
        $detail->setCellValue('D3', 10);
        $detail->setCellValue('E3', ''); // Empty satuan
        $detail->setCellValue('F3', 50000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $missingFieldErrors = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'E-MISSING-FIELD'));
        $this->assertNotEmpty($missingFieldErrors);
        $this->assertSame('E-MISSING-FIELD', $missingFieldErrors[0]->code);
        $this->assertSame('E', $missingFieldErrors[0]->column);
    }

    /**
     * T15: Level 2 berisi volume -> W-HEADER-HAS-VALUES, nilai tidak disimpan
     */
    public function test_t15_level_2_heading_with_volume_triggers_warning_and_value_ignored(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        // Level 2 has volume 100
        $detail->setCellValue('A3', 2);
        $detail->setCellValue('B3', 'I.A');
        $detail->setCellValue('C3', 'SUB PEKERJAAN');
        $detail->setCellValue('D3', 100);
        $detail->setCellValue('E3', 'M2');
        $detail->setCellValue('F3', 50000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $warnings = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'W-HEADER-HAS-VALUES'));
        $this->assertNotEmpty($warnings);
        $this->assertSame('W-HEADER-HAS-VALUES', $warnings[0]->code);

        $l2Row = $result->rows[1];
        $this->assertNull($l2Row->volume, 'Volume for heading must be null');
        $this->assertNull($l2Row->hargaSatuan, 'Harga satuan for heading must be null');
    }

    /**
     * T16: Level 5 tepat setelah Level 2 -> E-ORPHAN-MATERIAL
     */
    public function test_t16_orphan_material_triggers_e_orphan_material(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN STRUKTUR');

        $detail->setCellValue('A3', 2);
        $detail->setCellValue('B3', 'I.A');
        $detail->setCellValue('C3', 'STRUKTUR LANTAI 1');

        // Level 5 placed directly after Level 2 without Level 4 parent!
        $detail->setCellValue('A4', 5);
        $detail->setCellValue('B4', '5.0-1');
        $detail->setCellValue('C4', 'Semen Yatim');
        $detail->setCellValue('D4', 10);
        $detail->setCellValue('E4', 'Zak');
        $detail->setCellValue('F4', 70000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $orphanErrors = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'E-ORPHAN-MATERIAL'));
        $this->assertNotEmpty($orphanErrors);
        $this->assertSame('E-ORPHAN-MATERIAL', $orphanErrors[0]->code);
        $this->assertSame(4, $orphanErrors[0]->rowNo);
    }

    /**
     * T17: Baris berisi PPN 11% / JUMLAH -> W-TOTAL-ROW, baris dikeluarkan
     */
    public function test_t17_summary_row_excluded_with_w_total_row(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        $detail->setCellValue('A3', 4);
        $detail->setCellValue('B3', '1.0');
        $detail->setCellValue('C3', 'Pekerjaan Pondasi');
        $detail->setCellValue('D3', 10);
        $detail->setCellValue('E3', 'M3');
        $detail->setCellValue('F3', 100000);
        $detail->setCellValue('G3', 1000000);

        // Summary row
        $detail->setCellValue('A4', 1);
        $detail->setCellValue('C4', 'JUMLAH HARGA PEKERJAAN');
        $detail->setCellValue('G4', 1000000);

        // PPN row
        $detail->setCellValue('A5', 1);
        $detail->setCellValue('C5', 'PPN 11%');
        $detail->setCellValue('G5', 110000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $totalWarnings = array_values(array_filter($result->issues, fn (ValidationIssue $i) => $i->code === 'W-TOTAL-ROW'));
        $this->assertCount(2, $totalWarnings);

        // Verify rows count excludes summary rows (only heading and work item = 2 rows)
        $this->assertSame(2, $result->stats['rows']);
    }

    /**
     * T18: Satuan m², M^3, LS, bh -> Dinormalisasi ke M2, M3, Ls, Buah
     */
    public function test_t18_unit_normalization_maps_aliases_to_canonical(): void
    {
        $this->assertSame('M2', UnitNormalizer::normalize('m²')['canonical']);
        $this->assertSame('M3', UnitNormalizer::normalize('M^3')['canonical']);
        $this->assertSame('Ls', UnitNormalizer::normalize('LS')['canonical']);
        $this->assertSame('Buah', UnitNormalizer::normalize('bh')['canonical']);
    }

    /**
     * T19: File .xls / bukan zip -> E-TEMPLATE, tanpa crash
     */
    public function test_t19_non_xlsx_or_corrupted_file_returns_e_template_without_crash(): void
    {
        // 1. Non-xlsx extension
        $tmpXls = tempnam(sys_get_temp_dir(), 'test_').'.xls';
        file_put_contents($tmpXls, 'fake xls content');
        $this->tempFiles[] = $tmpXls;

        $resultXls = $this->parser->parse($tmpXls);
        $this->assertSame(1, $resultXls->stats['errors']);
        $this->assertSame('E-TEMPLATE', $resultXls->issues[0]->code);

        // 2. Corrupted file with .xlsx extension
        $tmpCorrupted = tempnam(sys_get_temp_dir(), 'test_corrupt_').'.xlsx';
        file_put_contents($tmpCorrupted, 'not a zip archive');
        $this->tempFiles[] = $tmpCorrupted;

        $resultCorrupt = $this->parser->parse($tmpCorrupted);
        $this->assertSame(1, $resultCorrupt->stats['errors']);
        $this->assertSame('E-TEMPLATE', $resultCorrupt->issues[0]->code);
    }

    /**
     * T20: Baris kosong di tengah data -> Dilewati, hierarki tetap benar
     */
    public function test_t20_empty_row_in_middle_of_data_is_skipped_preserving_hierarchy(): void
    {
        $spreadsheet = $this->makeMinimalValidSpreadsheet();
        $detail = $spreadsheet->getSheetByName('Detail RAB');

        // Heading 1
        $detail->setCellValue('A2', 1);
        $detail->setCellValue('B2', 'I');
        $detail->setCellValue('C2', 'PEKERJAAN SATU');

        // Blank row at row 3 (all A-F empty, formula in G)
        $detail->setCellValue('G3', '=ROUND(D3*F3,2)');

        // Work item at row 4
        $detail->setCellValue('A4', 4);
        $detail->setCellValue('B4', '1.0');
        $detail->setCellValue('C4', 'Item Setelah Baris Kosong');
        $detail->setCellValue('D4', 10);
        $detail->setCellValue('E4', 'M2');
        $detail->setCellValue('F4', 50000);
        $detail->setCellValue('G4', 500000);

        $tmp = $this->createTempXlsx($spreadsheet);
        $result = $this->parser->parse($tmp);

        $this->assertSame(2, $result->stats['rows']);
        $itemRow = $result->rows[1];
        $this->assertSame(4, $itemRow->rowNo);
        $this->assertSame(2, $itemRow->parentRowNo, 'Parent must be row 2 heading despite empty row 3');
    }
}
