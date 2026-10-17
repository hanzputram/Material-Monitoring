<?php

namespace App\Services\Rab\Dto;

class ParseResult
{
    /**
     * @param  array<string, mixed>  $project
     * @param  array<int, ParsedRow>  $rows
     * @param  array<int, ValidationIssue>  $issues
     * @param  array<string, int>  $stats
     */
    public function __construct(
        public array $project,
        public array $rows,
        public array $issues,
        public array $stats,
    ) {}

    public function hasErrors(): bool
    {
        return ($this->stats['errors'] ?? 0) > 0;
    }

    public function hasWarnings(): bool
    {
        return ($this->stats['warnings'] ?? 0) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'project' => $this->project,
            'rows' => array_map(fn (ParsedRow $row) => $row->toArray(), $this->rows),
            'issues' => array_map(fn (ValidationIssue $issue) => $issue->toArray(), $this->issues),
            'stats' => $this->stats,
        ];
    }
}
