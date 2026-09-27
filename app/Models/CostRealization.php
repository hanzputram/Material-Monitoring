<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CostRealization extends Model
{
    protected $fillable = [
        'project_id',
        'rab_item_id',
        'budget_amount',
        'actual_amount',
        'variance_amount',
        'variance_pct',
        'last_calculated_at',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_pct' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class);
    }

    public function updateActual(float $actualAmount): void
    {
        $this->actual_amount = $actualAmount;
        $this->variance_amount = $actualAmount - (float) $this->budget_amount;
        if ((float) $this->budget_amount > 0) {
            $this->variance_pct = round(($this->variance_amount / (float) $this->budget_amount) * 100, 2);
        } else {
            $this->variance_pct = $actualAmount > 0 ? 100.0 : 0.0;
        }
        $this->last_calculated_at = Carbon::now();
        $this->save();
    }
}
