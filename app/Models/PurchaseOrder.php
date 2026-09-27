<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'project_id',
        'supplier_id',
        'po_number',
        'po_date',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            return (float) $item->qty_ordered * (float) $item->unit_price;
        });
    }
}
