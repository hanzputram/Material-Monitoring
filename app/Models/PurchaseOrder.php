<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

    public function returns()
    {
        return $this->hasManyThrough(PurchaseReturn::class, DeliveryOrder::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            return (float) $item->qty_ordered * (float) $item->unit_price;
        });
    }

    /**
     * Total qty diterima per PO per material: [po_id => [material_id => qty]] dalam satu query.
     *
     * @param  array<int, int>  $purchaseOrderIds
     * @return array<int, array<int, float>>
     */
    public static function receivedQuantitiesFor(array $purchaseOrderIds): array
    {
        if (empty($purchaseOrderIds)) {
            return [];
        }

        $map = [];
        DeliveryOrderItem::query()
            ->whereIn('purchase_order_id', $purchaseOrderIds)
            ->groupBy('purchase_order_id', 'material_id')
            ->selectRaw('purchase_order_id, material_id, SUM(qty_received) as total')
            ->toBase()
            ->get()
            ->each(function ($row) use (&$map) {
                $map[(int) $row->purchase_order_id][(int) $row->material_id] = (float) $row->total;
            });

        return $map;
    }

    /**
     * Isi atribut `fulfillment` untuk banyak PO sekaligus tanpa query per item.
     * PO sebaiknya sudah eager-load `items.material.defaultUnit` dan `items.unit`.
     *
     * @param  Collection<int, PurchaseOrder>  $purchaseOrders
     * @return Collection<int, PurchaseOrder>
     */
    public static function attachFulfillment(Collection $purchaseOrders): Collection
    {
        $received = self::receivedQuantitiesFor($purchaseOrders->modelKeys());

        $doNumbers = $purchaseOrders->pluck('delivery_order_number')
            ->filter()
            ->map(fn ($n) => trim($n))
            ->unique()
            ->values();

        $usedDoNumbers = [];
        if ($doNumbers->isNotEmpty()) {
            DeliveryOrder::query()
                ->whereIn('project_id', $purchaseOrders->pluck('project_id')->unique()->values())
                ->whereIn('do_number', $doNumbers)
                ->get(['project_id', 'do_number'])
                ->each(function ($do) use (&$usedDoNumbers) {
                    $usedDoNumbers[$do->project_id.'|'.$do->do_number] = true;
                });
        }

        return $purchaseOrders->each(function (PurchaseOrder $po) use ($received, $usedDoNumbers) {
            $po->fulfillment = $po->getFulfillmentDetails($received[$po->id] ?? [], $usedDoNumbers);
        });
    }

    /**
     * Jumlah PO (non-cancelled) pada proyek yang masih memiliki item belum diterima penuh.
     * Dihitung dengan satu query agregat (dipakai badge sidebar di setiap halaman).
     */
    public static function pendingCountForProject(?int $projectId): int
    {
        if (! $projectId) {
            return 0;
        }

        $received = DeliveryOrderItem::query()
            ->whereNotNull('purchase_order_id')
            ->groupBy('purchase_order_id', 'material_id')
            ->selectRaw('purchase_order_id, material_id, SUM(qty_received) as qty_received_total')
            ->toBase();

        return (int) PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->leftJoinSub($received, 'received', function ($join) {
                $join->on('received.purchase_order_id', '=', 'purchase_order_items.purchase_order_id')
                    ->on('received.material_id', '=', 'purchase_order_items.material_id');
            })
            ->where('purchase_orders.project_id', $projectId)
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->whereRaw('purchase_order_items.qty_ordered - COALESCE(received.qty_received_total, 0) > 0')
            ->distinct()
            ->count('purchase_order_items.purchase_order_id');
    }

    /**
     * Dapatkan rincian pemenuhan/penerimaan untuk setiap item pada PO ini
     *
     * @param  array<int, float>|null  $receivedByMaterial  qty diterima per material (dari receivedQuantitiesFor)
     * @param  array<string, bool>|null  $usedDoNumbers  set "project_id|do_number" yang sudah dipakai
     */
    public function getFulfillmentDetails(?array $receivedByMaterial = null, ?array $usedDoNumbers = null): array
    {
        $items = [];
        $totalOrdered = 0;
        $totalReceived = 0;
        $hasPending = false;
        $hasReceived = false;

        $receivedByMaterial ??= self::receivedQuantitiesFor([$this->id])[$this->id] ?? [];

        foreach ($this->items as $it) {
            $receivedQty = (float) ($receivedByMaterial[$it->material_id] ?? 0);

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
            $isDoUsed = $usedDoNumbers !== null
                ? isset($usedDoNumbers[$this->project_id.'|'.$currentDoNumber])
                : DeliveryOrder::where('project_id', $this->project_id)
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
