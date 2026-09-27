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
