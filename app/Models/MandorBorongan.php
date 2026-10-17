<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MandorBorongan extends Model
{
    use HasFactory;

    protected $table = 'mandor_borongans';

    protected $fillable = [
        'project_id',
        'mandor_name',
        'mandor_phone',
        'work_scope',
        'spk_number',
        'contract_amount',
        'panjar_amount',
        'paid_amount',
        'progress_percent',
        'status',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $casts = [
        'contract_amount' => 'decimal:2',
        'panjar_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'progress_percent' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function panjars(): HasMany
    {
        return $this->hasMany(MandorBoronganPanjar::class, 'mandor_borongan_id')->orderByDesc('panjar_date')->orderByDesc('id');
    }

    /**
     * Nilai bersih hasil kontrak setelah dipotong panjar
     */
    public function getNetContractAmountAttribute(): float
    {
        return max(0, (float) $this->contract_amount - (float) ($this->panjar_amount ?? 0));
    }

    /**
     * Sisa pembayaran kontrak borongan (Nilai Kontrak - Potongan Panjar - Realisasi Terbayar)
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->contract_amount - (float) ($this->panjar_amount ?? 0) - (float) $this->paid_amount);
    }

    /**
     * Persentase realisasi pembayaran terhadap hasil bersih kontrak (%)
     */
    public function getPaymentPercentAttribute(): float
    {
        $net = $this->net_contract_amount;
        if ($net <= 0) {
            return (float) $this->contract_amount > 0 ? 100 : 0;
        }

        return round(((float) $this->paid_amount / $net) * 100, 1);
    }
}
