<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'worker_id',
        'assigned_trade',
        'daily_wage',
        'status',
        'start_date',
        'end_date',
        'notes',
        'added_by',
    ];

    protected function casts(): array
    {
        return [
            'daily_wage' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function getEffectiveTradeAttribute(): string
    {
        return ! empty($this->assigned_trade) ? $this->assigned_trade : ($this->worker?->trade ?? 'Pekerja');
    }

    public function getFormattedDailyWageAttribute(): string
    {
        return 'Rp '.number_format((float) $this->daily_wage, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif Bekerja',
            'standby' => 'Standby / Cadangan',
            'completed' => 'Selesai Penugasan',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'standby' => 'bg-amber-50 text-amber-700 border-amber-200',
            'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }
}
