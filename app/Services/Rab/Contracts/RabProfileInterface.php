<?php

namespace App\Services\Rab\Contracts;

use App\Services\Rab\Dto\ParseResult;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface RabProfileInterface
{
    public function id(): string;

    public function name(): string;

    public function detect(Spreadsheet $spreadsheet): bool;

    public function parse(string $filePath): ParseResult;
}
