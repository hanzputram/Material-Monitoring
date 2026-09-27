<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabItemMaterial extends Model
{
    protected $fillable = [
        'rab_item_id',
        'material_id',
        'volume',
        'unit_id',
        'unit_price',
        'total_price',
        'input_by',
    ];

    protected $casts = [
        'volume' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saved(function ($itemMaterial) {
            $item = $itemMaterial->rabItem;
            if ($item) {
                $item->is_composite = true;
                $item->saveQuietly();
                if ($item->rabNode) {
                    MaterialRealization::recalculateForProjectMaterial(
                        $item->rabNode->project_id,
                        $itemMaterial->material_id
                    );
                }
            }
        });

        static::deleted(function ($itemMaterial) {
            $item = $itemMaterial->rabItem;
            if ($item) {
                if ($item->materials()->count() === 0) {
                    $item->is_composite = false;
                    $item->saveQuietly();
                }
                if ($item->rabNode) {
                    MaterialRealization::recalculateForProjectMaterial(
                        $item->rabNode->project_id,
                        $itemMaterial->material_id
                    );
                }
            }
        });
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class, 'rab_item_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function inputUser()
    {
        return $this->belongsTo(User::class, 'input_by');
    }
}
