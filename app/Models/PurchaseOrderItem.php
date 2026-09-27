<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'material_id',
        'qty_ordered',
        'unit_id',
        'unit_price',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:4',
        'unit_price' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->qty_ordered * (float) $this->unit_price;
    }
}
