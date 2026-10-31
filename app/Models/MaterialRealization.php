<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MaterialRealization extends Model
{
    protected $fillable = [
        'project_id',
        'material_id',
        'planned_qty',
        'actual_qty',
        'variance_qty',
        'variance_pct',
        'status',
        'last_calculated_at',
    ];

    protected $casts = [
        'planned_qty' => 'decimal:4',
        'actual_qty' => 'decimal:4',
        'variance_qty' => 'decimal:4',
        'variance_pct' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function varianceValidations()
    {
        return $this->hasMany(MaterialVarianceValidation::class);
    }

    public function getEvalTypeAttribute(): string
    {
        $actual = (float) $this->actual_qty;
        $planned = (float) $this->planned_qty;
        if ($this->status === 'kelebihan' || $actual > $planned) {
            return 'over';
        } elseif ($actual == 0) {
            return 'pending';
        } elseif ($actual < $planned) {
            return 'partial';
        }

        return 'normal';
    }

    public static function recalculateForProjectMaterial(int $projectId, int $materialId): self
    {
        $project = Project::find($projectId);
        $material = Material::find($materialId);

        if (! $project || ! $material) {
            throw new \InvalidArgumentException('Project or Material not found');
        }

        $quantities = self::aggregateQuantities($projectId, [$materialId]);
        $existing = self::where('project_id', $projectId)->where('material_id', $materialId)->first();

        return self::applyRecalculation($project, $material, $quantities, $existing);
    }

    public static function recalculateAllForProject(int $projectId): void
    {
        $project = Project::find($projectId);
        if (! $project) {
            return;
        }

        // Get all materials used in this project's RAB (leaf) or DOs
        $materialIdsFromRab = self::projectRabMaterialsQuery($projectId)
            ->whereNotExists(self::breakdownExistsSubquery())
            ->distinct()
            ->pluck('rab_item_materials.material_id')
            ->all();

        $materialIdsFromDo = DeliveryOrderItem::query()
            ->join('delivery_orders', 'delivery_orders.id', '=', 'delivery_order_items.delivery_order_id')
            ->where('delivery_orders.project_id', $projectId)
            ->distinct()
            ->pluck('delivery_order_items.material_id')
            ->all();

        $allMaterialIds = array_values(array_unique(array_map('intval', array_merge($materialIdsFromRab, $materialIdsFromDo))));

        // Seluruh angka dihitung dengan beberapa query agregat untuk semua material sekaligus
        $quantities = self::aggregateQuantities($projectId, $allMaterialIds);
        $materials = Material::whereIn('id', $allMaterialIds)->get()->keyBy('id');
        $existingRealizations = self::where('project_id', $projectId)->get()->keyBy('material_id');

        foreach ($allMaterialIds as $materialId) {
            $material = $materials->get($materialId);
            if ($material) {
                self::applyRecalculation($project, $material, $quantities, $existingRealizations->get($materialId));
            }
        }

        // Clean up orphaned realizations no longer in either RAB or DO
        $orphans = $existingRealizations->reject(fn ($realization) => in_array((int) $realization->material_id, $allMaterialIds, true));

        if ($orphans->isNotEmpty()) {
            $orphanIds = $orphans->modelKeys();
            Alert::where('project_id', $projectId)
                ->where('reference_type', self::class)
                ->whereIn('reference_id', $orphanIds)
                ->delete();
            MaterialVarianceValidation::whereIn('material_realization_id', $orphanIds)->delete();
            self::whereIn('id', $orphanIds)->delete();
        }
    }

    /**
     * Hitung planned / received / returned qty untuk sekumpulan material dalam satu proyek.
     *
     * @param  array<int, int>  $materialIds
     * @return array{planned: array<int, float>, received: array<int, float>, received_unallocated: array<int, float>, returned: array<int, float>, parents: array<int, bool>}
     */
    protected static function aggregateQuantities(int $projectId, array $materialIds): array
    {
        $result = ['planned' => [], 'received' => [], 'received_unallocated' => [], 'returned' => [], 'parents' => []];
        if (empty($materialIds)) {
            return $result;
        }

        foreach (array_chunk($materialIds, 500) as $chunk) {
            // 1. planned_qty: hanya material daun (leaf). Jika material hasil jadi sudah di-breakdown,
            // rincian komponen breakdown-lah yang dihitung kuotanya.
            $planned = self::projectRabMaterialsQuery($projectId)
                ->whereIn('rab_item_materials.material_id', $chunk)
                ->whereNotExists(self::breakdownExistsSubquery())
                ->groupBy('rab_item_materials.material_id')
                ->selectRaw('rab_item_materials.material_id as material_id, SUM(rab_item_materials.volume) as total')
                ->pluck('total', 'material_id');

            // 2. actual_qty: DO tervalidasi dikurangi retur yang selesai
            $receivedQuery = fn () => DeliveryOrderItem::query()
                ->join('delivery_orders', 'delivery_orders.id', '=', 'delivery_order_items.delivery_order_id')
                ->where('delivery_orders.project_id', $projectId)
                ->where('delivery_orders.status', 'validated')
                ->whereIn('delivery_order_items.material_id', $chunk)
                ->groupBy('delivery_order_items.material_id')
                ->selectRaw('delivery_order_items.material_id as material_id, SUM(delivery_order_items.qty_received) as total');

            $received = $receivedQuery()->pluck('total', 'material_id');

            // Penerimaan yang TIDAK berasal dari PO teralokasi ke item RAB (level 4 tanpa BOM)
            $receivedUnallocated = $receivedQuery()
                ->whereNotExists(fn ($q) => $q->selectRaw('1')
                    ->from('purchase_order_items as allocated_poi')
                    ->whereColumn('allocated_poi.purchase_order_id', 'delivery_order_items.purchase_order_id')
                    ->whereColumn('allocated_poi.material_id', 'delivery_order_items.material_id')
                    ->whereNotNull('allocated_poi.rab_item_id'))
                ->pluck('total', 'material_id');

            $returned = PurchaseReturnItem::query()
                ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                ->where('purchase_returns.project_id', $projectId)
                ->where('purchase_returns.status', 'completed')
                ->whereIn('purchase_return_items.material_id', $chunk)
                ->groupBy('purchase_return_items.material_id')
                ->selectRaw('purchase_return_items.material_id as material_id, SUM(purchase_return_items.qty_returned) as total')
                ->pluck('total', 'material_id');

            $parents = self::projectRabMaterialsQuery($projectId)
                ->whereIn('rab_item_materials.material_id', $chunk)
                ->whereExists(self::breakdownExistsSubquery())
                ->distinct()
                ->pluck('rab_item_materials.material_id');

            foreach ($planned as $id => $total) {
                $result['planned'][(int) $id] = (float) $total;
            }
            foreach ($received as $id => $total) {
                $result['received'][(int) $id] = (float) $total;
            }
            foreach ($receivedUnallocated as $id => $total) {
                $result['received_unallocated'][(int) $id] = (float) $total;
            }
            foreach ($returned as $id => $total) {
                $result['returned'][(int) $id] = (float) $total;
            }
            foreach ($parents as $id) {
                $result['parents'][(int) $id] = true;
            }
        }

        return $result;
    }

    /**
     * @param  array{planned: array<int, float>, received: array<int, float>, received_unallocated: array<int, float>, returned: array<int, float>, parents: array<int, bool>}  $quantities
     */
    protected static function applyRecalculation(Project $project, Material $material, array $quantities, ?self $existing): self
    {
        $projectId = $project->id;
        $materialId = $material->id;

        $plannedQty = (float) ($quantities['planned'][$materialId] ?? 0);
        $receivedQty = (float) ($quantities['received'][$materialId] ?? 0);
        $returnedQty = (float) ($quantities['returned'][$materialId] ?? 0);
        $actualQty = max(0, $receivedQty - $returnedQty);

        // 3. Variance
        $varianceQty = $actualQty - $plannedQty;
        $variancePct = 0.0;
        if ($plannedQty > 0) {
            $variancePct = round(($varianceQty / $plannedQty) * 100, 2);
        } elseif ($actualQty > 0) {
            $variancePct = 100.0;
        }

        // 4. Status determination based on project thresholds
        $isParentWithBreakdown = $quantities['parents'][$materialId] ?? false;

        // Material di luar BOM yang seluruh penerimaannya berasal dari PO teralokasi ke item RAB
        // (RAB tanpa level 5) dipantau lewat monitoring item pekerjaan, bukan sebagai material
        // "melebihi RAB" dengan rencana 0.
        $unallocatedReceived = (float) ($quantities['received_unallocated'][$materialId] ?? 0);
        $coveredByItemMonitoring = $plannedQty == 0 && ! $isParentWithBreakdown && $receivedQty > 0 && $unallocatedReceived <= 0;

        if (($isParentWithBreakdown && $plannedQty == 0 && $actualQty == 0) || $coveredByItemMonitoring) {
            if ($existing) {
                Alert::where('project_id', $projectId)
                    ->where('reference_type', self::class)
                    ->where('reference_id', $existing->id)
                    ->delete();
                $existing->varianceValidations()->delete();
                $existing->delete();
            }

            return new self([
                'project_id' => $projectId,
                'material_id' => $materialId,
                'planned_qty' => 0,
                'actual_qty' => 0,
                'variance_qty' => 0,
                'variance_pct' => 0,
                'status' => 'normal',
            ]);
        }

        $overThreshold = (float) ($project->alert_over_threshold_pct ?? 10.00);
        $underThreshold = (float) ($project->alert_under_threshold_pct ?? 5.00);

        $status = 'normal';
        if ($variancePct >= $overThreshold && $actualQty > $plannedQty) {
            $status = 'kelebihan';
        } elseif ($variancePct <= -$underThreshold && $plannedQty > 0) {
            $status = 'kekurangan';
        }

        $realization = $existing ?? new self([
            'project_id' => $projectId,
            'material_id' => $materialId,
        ]);
        $realization->fill([
            'planned_qty' => $plannedQty,
            'actual_qty' => $actualQty,
            'variance_qty' => $varianceQty,
            'variance_pct' => $variancePct,
            'status' => $status,
            'last_calculated_at' => Carbon::now(),
        ])->save();

        // 5. Trigger alert if out of threshold
        if ($status !== 'normal') {
            $type = $status === 'kelebihan' ? 'material_over' : 'material_under';
            $severity = abs($variancePct) >= ($overThreshold * 2) ? 'critical' : 'warning';
            $message = $status === 'kelebihan'
                ? "Material {$material->name} melebihi rencana RAB sebesar {$variancePct}% (Rencana: {$plannedQty}, Aktual: {$actualQty})"
                : "Material {$material->name} kurang dari rencana RAB sebesar ".abs($variancePct)."% (Rencana: {$plannedQty}, Aktual: {$actualQty})";

            $alert = Alert::firstOrNew([
                'project_id' => $projectId,
                'type' => $type,
                'reference_type' => self::class,
                'reference_id' => $realization->id,
            ]);

            // Alert yang sudah dibaca/diselesaikan hanya dibuka ulang bila kondisinya berubah
            if (! $alert->exists || $alert->message !== $message || $alert->severity !== $severity) {
                $alert->fill([
                    'message' => $message,
                    'severity' => $severity,
                    'status' => 'unread',
                ])->save();
            }
        } else {
            // If back to normal, mark alerts resolved
            Alert::where('project_id', $projectId)
                ->where('reference_type', self::class)
                ->where('reference_id', $realization->id)
                ->where('status', '!=', 'resolved')
                ->update(['status' => 'resolved']);
        }

        return $realization;
    }

    /**
     * Query dasar rab_item_materials milik suatu proyek (join langsung, bukan whereHas bertingkat).
     */
    protected static function projectRabMaterialsQuery(int $projectId): Builder
    {
        return RabItemMaterial::query()
            ->join('rab_items', 'rab_items.id', '=', 'rab_item_materials.rab_item_id')
            ->join('rab_nodes', 'rab_nodes.id', '=', 'rab_items.rab_node_id')
            ->where('rab_nodes.project_id', $projectId);
    }

    protected static function breakdownExistsSubquery(): \Closure
    {
        return fn ($query) => $query->selectRaw('1')
            ->from('rab_item_materials as breakdown_children')
            ->whereColumn('breakdown_children.parent_id', 'rab_item_materials.id');
    }
}
