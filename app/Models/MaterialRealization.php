<?php

namespace App\Models;

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

    public static function recalculateForProjectMaterial(int $projectId, int $materialId): self
    {
        $project = Project::find($projectId);
        $material = Material::find($materialId);

        if (!$project || !$material) {
            throw new \InvalidArgumentException("Project or Material not found");
        }

        // 1. Calculate planned_qty from rab_item_materials in this project
        $plannedQty = (float) RabItemMaterial::whereHas('rabItem.rabNode', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })->where('material_id', $materialId)->sum('volume');

        // 2. Calculate actual_qty from validated delivery orders
        $actualQty = (float) DeliveryOrderItem::whereHas('deliveryOrder', function ($query) use ($projectId) {
            $query->where('project_id', $projectId)
                  ->where('status', 'validated');
        })->where('material_id', $materialId)->sum('qty_received');

        // 3. Variance
        $varianceQty = $actualQty - $plannedQty;
        $variancePct = 0.0;
        if ($plannedQty > 0) {
            $variancePct = round(($varianceQty / $plannedQty) * 100, 2);
        } elseif ($actualQty > 0) {
            $variancePct = 100.0;
        }

        // 4. Status determination based on project thresholds
        $overThreshold = (float) ($project->alert_over_threshold_pct ?? 10.00);
        $underThreshold = (float) ($project->alert_under_threshold_pct ?? 5.00);

        $status = 'normal';
        if ($variancePct >= $overThreshold && $actualQty > $plannedQty) {
            $status = 'kelebihan';
        } elseif ($variancePct <= -$underThreshold && $plannedQty > 0) {
            $status = 'kekurangan';
        }

        $realization = self::updateOrCreate(
            [
                'project_id' => $projectId,
                'material_id' => $materialId,
            ],
            [
                'planned_qty' => $plannedQty,
                'actual_qty' => $actualQty,
                'variance_qty' => $varianceQty,
                'variance_pct' => $variancePct,
                'status' => $status,
                'last_calculated_at' => Carbon::now(),
            ]
        );

        // 5. Trigger alert if out of threshold
        if ($status !== 'normal') {
            $type = $status === 'kelebihan' ? 'material_over' : 'material_under';
            $severity = abs($variancePct) >= ($overThreshold * 2) ? 'critical' : 'warning';
            $message = $status === 'kelebihan'
                ? "Material {$material->name} melebihi rencana RAB sebesar {$variancePct}% (Rencana: {$plannedQty}, Aktual: {$actualQty})"
                : "Material {$material->name} kurang dari rencana RAB sebesar " . abs($variancePct) . "% (Rencana: {$plannedQty}, Aktual: {$actualQty})";

            Alert::updateOrCreate(
                [
                    'project_id' => $projectId,
                    'type' => $type,
                    'reference_type' => self::class,
                    'reference_id' => $realization->id,
                ],
                [
                    'message' => $message,
                    'severity' => $severity,
                    'status' => 'unread',
                ]
            );
        } else {
            // If back to normal, mark alerts resolved
            Alert::where('project_id', $projectId)
                ->where('reference_type', self::class)
                ->where('reference_id', $realization->id)
                ->update(['status' => 'resolved']);
        }

        return $realization;
    }

    public static function recalculateAllForProject(int $projectId): void
    {
        // Get all materials used in this project's RAB or DOs
        $materialIdsFromRab = RabItemMaterial::whereHas('rabItem.rabNode', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })->pluck('material_id')->toArray();

        $materialIdsFromDo = DeliveryOrderItem::whereHas('deliveryOrder', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })->pluck('material_id')->toArray();

        $allMaterialIds = array_unique(array_merge($materialIdsFromRab, $materialIdsFromDo));

        foreach ($allMaterialIds as $materialId) {
            self::recalculateForProjectMaterial($projectId, $materialId);
        }
    }
}
