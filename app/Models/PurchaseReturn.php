<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'supplier_id',
        'delivery_order_id',
        'return_number',
        'return_date',
        'compensation_type',
        'status',
        'attachment_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum('total_price');
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
