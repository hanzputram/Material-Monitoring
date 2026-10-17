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
        'delivery_order_number',
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
        return $this->belongsToMany(DeliveryOrder::class, 'delivery_order_purchase_order')->withTimestamps();
    }

    public function directDeliveryOrders()
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

    /**
     * Dapatkan rincian pemenuhan/penerimaan untuk setiap item pada PO ini
     */
    public function getFulfillmentDetails(): array
    {
        $items = [];
        $totalOrdered = 0;
        $totalReceived = 0;
        $hasPending = false;
        $hasReceived = false;

        foreach ($this->items as $it) {
            $receivedQty = (float) DeliveryOrderItem::where('purchase_order_id', $this->id)
                ->where('material_id', $it->material_id)
                ->sum('qty_received');

            $orderedQty = (float) $it->qty_ordered;
            $remainingQty = max(0, $orderedQty - $receivedQty);
            $pct = $orderedQty > 0 ? min(100, round(($receivedQty / $orderedQty) * 100, 1)) : 100;

            if ($remainingQty > 0) {
                $hasPending = true;
            }
            if ($receivedQty > 0) {
                $hasReceived = true;
            }

            $totalOrdered += $orderedQty;
            $totalReceived += min($orderedQty, $receivedQty);

            $items[] = [
                'item_id' => $it->id,
                'material_id' => $it->material_id,
                'material_name' => $it->material?->name ?? 'Item',
                'material_code' => $it->material?->code ?? '-',
                'unit_code' => $it->unit?->code ?? ($it->material?->defaultUnit?->code ?? '-'),
                'qty_ordered' => $orderedQty,
                'qty_received' => $receivedQty,
                'qty_remaining' => $remainingQty,
                'percentage' => $pct,
                'is_fulfilled' => $remainingQty <= 0,
            ];
        }

        $overallPct = $totalOrdered > 0 ? min(100, round(($totalReceived / $totalOrdered) * 100, 1)) : 100;

        $currentDoNumber = $this->delivery_order_number ? trim($this->delivery_order_number) : null;
        $isDoUsed = false;
        if ($currentDoNumber) {
            $isDoUsed = DeliveryOrder::where('project_id', $this->project_id)
                ->where('do_number', $currentDoNumber)
                ->exists();
        }

        if (! $hasReceived) {
            $fulfillmentStatus = 'unfulfilled';
            $statusLabel = 'Belum Ada Pengiriman';
        } elseif ($hasPending) {
            $fulfillmentStatus = 'partial';
            $statusLabel = 'Sebagian Diterima (Menggantung)';
        } else {
            $fulfillmentStatus = 'fulfilled';
            $statusLabel = 'Selesai (100% Diterima)';
        }

        return [
            'items' => $items,
            'total_ordered' => $totalOrdered,
            'total_received' => $totalReceived,
            'percentage' => $overallPct,
            'has_pending' => $hasPending,
            'has_received' => $hasReceived,
            'is_partial' => ($hasReceived && $hasPending),
            'fulfillment_status' => $fulfillmentStatus,
            'status_label' => $statusLabel,
            'current_do_number' => $currentDoNumber,
            'is_current_do_used' => $isDoUsed,
            'needs_new_do_number' => ($hasPending && ($isDoUsed || empty($currentDoNumber))),
        ];
    }
}
