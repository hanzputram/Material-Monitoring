<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'project_id',
        'purchase_order_id',
        'supplier_id',
        'do_number',
        'do_date',
        'attachment_path',
        'received_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'do_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrders()
    {
        return $this->belongsToMany(PurchaseOrder::class, 'delivery_order_purchase_order')->withTimestamps();
    }

    public function getLinkedPoNumbersAttribute(): string
    {
        $numbers = $this->purchaseOrders->pluck('po_number')->filter();
        if ($numbers->isEmpty() && $this->purchaseOrder) {
            return $this->purchaseOrder->po_number;
        }

        return $numbers->isNotEmpty() ? $numbers->implode(', ') : '-';
    }

    public function hasNonPoItems(): bool
    {
        return $this->items->contains(function ($item) {
            return is_null($item->purchase_order_id);
        });
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function varianceValidations()
    {
        return $this->hasMany(MaterialVarianceValidation::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function syncRealizations(): void
    {
        foreach ($this->items as $item) {
            MaterialRealization::recalculateForProjectMaterial(
                $this->project_id,
                $item->material_id
            );
        }
    }
}
