<?php

namespace App\Services\Rab\Dto;

class ParsedRow
{
    /**
     * @param  int  $level  1..5
     * @param  string  $itemType  heading | work_item | material
     */
    public function __construct(
        public int $rowNo,
        public int $sortOrder,
        public int $level,
        public string $itemType,
        public ?string $kode,
        public string $uraian,
        public ?float $volume,
        public ?string $satuan,
        public ?string $satuanRaw,
        public ?float $hargaSatuan,
        public ?float $jumlahHargaFile,
        public ?float $jumlahHargaCalc,
        public ?int $parentRowNo,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'row_no' => $this->rowNo,
            'sort_order' => $this->sortOrder,
            'level' => $this->level,
            'item_type' => $this->itemType,
            'kode' => $this->kode,
            'uraian' => $this->uraian,
            'volume' => $this->volume,
            'satuan' => $this->satuan,
            'satuan_raw' => $this->satuanRaw,
            'harga_satuan' => $this->hargaSatuan,
            'jumlah_harga_file' => $this->jumlahHargaFile,
            'jumlah_harga_calc' => $this->jumlahHargaCalc,
            'parent_row_no' => $this->parentRowNo,
        ];
    }
}
