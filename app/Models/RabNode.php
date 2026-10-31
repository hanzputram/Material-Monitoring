<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabNode extends Model
{
    protected $fillable = [
        'project_id',
        'parent_id',
        'level',
        'code',
        'name',
        'sort_order',
        'subtotal_cache',
    ];

    protected $casts = [
        'level' => 'integer',
        'sort_order' => 'integer',
        'subtotal_cache' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::deleted(function ($node) {
            if ($node->project_id) {
                MaterialRealization::recalculateAllForProject($node->project_id);
            }
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function parent()
    {
        return $this->belongsTo(RabNode::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(RabNode::class, 'parent_id')->orderBy('sort_order');
    }

    public function rabItems()
    {
        return $this->hasMany(RabItem::class, 'rab_node_id')->orderBy('sort_order');
    }

    /**
     * Daftar relasi eager-load untuk seluruh pohon RAB (Level 1-3 + item Level 4),
     * sehingga traversal pohon di controller/view tidak memicu query N+1.
     *
     * @param  array<int, string>  $itemRelations  relasi tambahan untuk tiap RabItem
     * @return array<int, string>
     */
    public static function treeRelations(array $itemRelations = []): array
    {
        $relations = [];
        foreach (['', 'children.', 'children.children.'] as $prefix) {
            $relations[] = $prefix.'rabItems';
            foreach ($itemRelations as $relation) {
                $relations[] = $prefix.'rabItems.'.$relation;
            }
        }

        // Level 3 tidak punya anak, tetapi relasi dimuat agar akses ->children tidak query ulang
        $relations[] = 'children.children.children';

        return $relations;
    }

    public function getActualCostTotalAttribute(): float
    {
        $itemsTotal = (float) $this->rabItems->sum(fn ($item) => (float) $item->actual_cost);
        $childrenTotal = (float) $this->children->sum(fn ($child) => (float) $child->actual_cost_total);

        return $itemsTotal + $childrenTotal;
    }

    public function refreshSubtotal(bool $bubbleUp = true): float
    {
        $itemsTotal = (float) $this->rabItems()->sum('total_price');
        $childrenTotal = (float) $this->children()->sum('subtotal_cache');
        $total = $itemsTotal + $childrenTotal;

        $this->subtotal_cache = $total;
        $this->saveQuietly();

        if ($bubbleUp && $this->parent_id && $this->parent) {
            $this->parent->refreshSubtotal(true);
        }

        return $total;
    }
}
