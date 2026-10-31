<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CostRealization extends Model
{
    protected $fillable = [
        'project_id',
        'rab_item_id',
        'budget_amount',
        'actual_amount',
        'variance_amount',
        'variance_pct',
        'last_calculated_at',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_pct' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rabItem()
    {
        return $this->belongsTo(RabItem::class);
    }

    /**
     * Sinkronkan budget_amount seluruh item RAB proyek dalam satu upsert massal.
     */
    public static function syncBudgetsForProject(int $projectId): void
    {
        $rows = RabItem::query()
            ->join('rab_nodes', 'rab_nodes.id', '=', 'rab_items.rab_node_id')
            ->where('rab_nodes.project_id', $projectId)
            ->get(['rab_items.id', 'rab_items.total_price'])
            ->map(fn ($item) => [
                'project_id' => $projectId,
                'rab_item_id' => $item->id,
                'budget_amount' => (float) $item->total_price,
            ])
            ->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            static::upsert($chunk, ['project_id', 'rab_item_id'], ['budget_amount', 'updated_at']);
        }
    }

    public function updateActual(float $actualAmount): void
    {
        $this->actual_amount = $actualAmount;
        $this->variance_amount = $actualAmount - (float) $this->budget_amount;
        if ((float) $this->budget_amount > 0) {
            $this->variance_pct = round(($this->variance_amount / (float) $this->budget_amount) * 100, 2);
        } else {
            $this->variance_pct = $actualAmount > 0 ? 100.0 : 0.0;
        }
        $this->last_calculated_at = Carbon::now();
        $this->save();
    }
}
