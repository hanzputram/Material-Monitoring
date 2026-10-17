<?php

namespace App\Services\Rab\Dto;

class ValidationIssue
{
    /**
     * @param  string  $severity  ERROR | WARNING | INFO
     */
    public function __construct(
        public string $severity,
        public string $code,
        public ?int $rowNo,
        public ?string $column,
        public string $message,
        public mixed $value = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'row_no' => $this->rowNo,
            'column' => $this->column,
            'message' => $this->message,
            'value' => $this->value,
        ];
    }
}
