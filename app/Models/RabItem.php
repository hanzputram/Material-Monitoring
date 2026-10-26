<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabItem extends Model
{
    protected $fillable = [
        'rab_node_id',
        'item_no',
        'name',
        'volume',
        'unit_id',
        'unit_price',
        'total_price',
        'is_composite',
        'sort_order',
    ];

    protected $casts = [
        'volume' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_composite' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted()
    {
        static::saved(function ($item) {
            if ($item->rabNode) {
                $item->rabNode->refreshSubtotal();
                // Auto sync to cost_realizations
                CostRealization::updateOrCreate(
                    [
                        'project_id' => $item->rabNode->project_id,
                        'rab_item_id' => $item->id,
                    ],
                    [
                        'budget_amount' => $item->total_price,
                    ]
                );
            }
        });

        static::deleted(function ($item) {
            if ($item->rabNode) {
                $item->rabNode->refreshSubtotal();
            }
            CostRealization::where('rab_item_id', $item->id)->delete();
        });
    }

    public function getActualCostAttribute(): float
    {
        $cr = $this->costRealization;
        if ($cr && (float) $cr->actual_amount > 0) {
            return (float) $cr->actual_amount;
        }

        if ($this->relationLoaded('purchaseOrderItems')) {
            return (float) $this->purchaseOrderItems
                ->filter(fn ($poi) => $poi->purchaseOrder && $poi->purchaseOrder->status !== 'cancelled')
                ->sum(fn ($poi) => (float) $poi->qty_ordered * (float) $poi->unit_price);
        }

        return (float) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->get()
            ->sum(fn ($poi) => (float) $poi->qty_ordered * (float) $poi->unit_price);
    }

    public function syncCostRealization(): CostRealization
    {
        $actual = (float) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->get()
            ->sum(fn ($poi) => (float) $poi->qty_ordered * (float) $poi->unit_price);

        $budget = (float) $this->total_price;
        $variance = $actual - $budget;
        $variancePct = $budget > 0 ? round(($variance / $budget) * 100, 2) : ($actual > 0 ? 100.0 : 0.0);

        return CostRealization::updateOrCreate(
            [
                'project_id' => $this->rabNode?->project_id,
                'rab_item_id' => $this->id,
            ],
            [
                'budget_amount' => $budget,
                'actual_amount' => $actual,
                'variance_amount' => $variance,
                'variance_pct' => $variancePct,
                'last_calculated_at' => now(),
            ]
        );
    }

    public function rabNode()
    {
        return $this->belongsTo(RabNode::class, 'rab_node_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function materials()
    {
        return $this->hasMany(RabItemMaterial::class, 'rab_item_id');
    }

    public function rootMaterials()
    {
        return $this->hasMany(RabItemMaterial::class, 'rab_item_id')->whereNull('parent_id');
    }

    public function costRealization()
    {
        return $this->hasOne(CostRealization::class, 'rab_item_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'rab_item_id');
    }
}
