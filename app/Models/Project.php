<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'prototype_type',
        'floor_count',
        'budget_year',
        'location_kds',
        'foundation_type',
        'status',
        'alert_over_threshold_pct',
        'alert_under_threshold_pct',
        'created_by',
    ];

    protected $casts = [
        'floor_count' => 'integer',
        'alert_over_threshold_pct' => 'decimal:2',
        'alert_under_threshold_pct' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user')->withPivot('role_in_project')->withTimestamps();
    }

    public function rabNodes()
    {
        return $this->hasMany(RabNode::class)->orderBy('sort_order');
    }

    public function rootRabNodes()
    {
        return $this->hasMany(RabNode::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function materialRealizations()
    {
        return $this->hasMany(MaterialRealization::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class)->orderByDesc('created_at');
    }

    public function activeAlerts()
    {
        return $this->hasMany(Alert::class)->where('status', 'unread')->orderByDesc('created_at');
    }

    public function projectEquipment()
    {
        return $this->hasMany(ProjectEquipment::class);
    }

    public function projectWorkers()
    {
        return $this->hasMany(ProjectWorker::class);
    }

    public function workers()
    {
        return $this->belongsToMany(Worker::class, 'project_workers')
            ->withPivot(['id', 'assigned_trade', 'daily_wage', 'status', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    public function costRealizations()
    {
        return $this->hasMany(CostRealization::class);
    }

    public function mandorBorongans()
    {
        return $this->hasMany(MandorBorongan::class);
    }

    public function purchasePayments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getTotalRabAttribute(): float
    {
        if ($this->relationLoaded('rootRabNodes')) {
            return (float) $this->rootRabNodes->sum('subtotal_cache');
        }

        return (float) $this->rootRabNodes()->sum('subtotal_cache');
    }

    public function getTotalRealizationAttribute(): float
    {
        return (float) $this->invoices()->where('status', 'validated')->sum('amount');
    }

    public function recalculateAllSubtotals(): void
    {
        // Dua query agregat + roll-up bottom-up di memori (bukan 3 query per node)
        $nodes = $this->rabNodes()->get();
        if ($nodes->isEmpty()) {
            return;
        }

        $itemTotals = RabItem::query()
            ->whereIn('rab_node_id', $nodes->modelKeys())
            ->groupBy('rab_node_id')
            ->selectRaw('rab_node_id, SUM(total_price) as total')
            ->pluck('total', 'rab_node_id');

        $childrenByParent = $nodes->groupBy('parent_id');
        $totals = [];
        $compute = function (int $nodeId) use (&$compute, &$totals, $itemTotals, $childrenByParent): float {
            if (isset($totals[$nodeId])) {
                return $totals[$nodeId];
            }

            $total = (float) ($itemTotals[$nodeId] ?? 0);
            foreach ($childrenByParent->get($nodeId, []) as $child) {
                $total += $compute($child->id);
            }

            return $totals[$nodeId] = $total;
        };

        foreach ($nodes as $node) {
            $total = round($compute($node->id), 2);
            if (abs((float) $node->subtotal_cache - $total) > 0.001) {
                $node->subtotal_cache = $total;
                $node->saveQuietly();
            }
        }
    }
}
