<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worker extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'trade',
        'phone',
        'nik',
        'daily_rate',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function projectWorkers(): HasMany
    {
        return $this->hasMany(ProjectWorker::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_workers')
            ->withPivot(['id', 'assigned_trade', 'daily_wage', 'status', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedDailyRateAttribute(): string
    {
        return 'Rp '.number_format((float) $this->daily_rate, 0, ',', '.');
    }

    public function getTradeColorAttribute(): string
    {
        $trade = strtolower($this->trade ?? '');

        if (str_contains($trade, 'mandor')) {
            return 'bg-purple-50 text-purple-700 border-purple-200';
        }
        if (str_contains($trade, 'batu')) {
            return 'bg-amber-50 text-amber-700 border-amber-200';
        }
        if (str_contains($trade, 'besi')) {
            return 'bg-blue-50 text-blue-700 border-blue-200';
        }
        if (str_contains($trade, 'kayu')) {
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        }
        if (str_contains($trade, 'cat')) {
            return 'bg-pink-50 text-pink-700 border-pink-200';
        }
        if (str_contains($trade, 'listrik') || str_contains($trade, 'me')) {
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        }
        if (str_contains($trade, 'plafon') || str_contains($trade, 'gypsum')) {
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        }
        if (str_contains($trade, 'las')) {
            return 'bg-orange-50 text-orange-700 border-orange-200';
        }

        return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}
