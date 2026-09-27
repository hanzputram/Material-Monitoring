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
            }
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
        });

        static::deleted(function ($item) {
            if ($item->rabNode) {
                $item->rabNode->refreshSubtotal();
            }
            CostRealization::where('rab_item_id', $item->id)->delete();
        });
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
}
