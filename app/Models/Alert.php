<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'project_id',
        'type', // material_over, material_under, cost_over_budget
        'reference_type',
        'reference_id',
        'message',
        'severity', // info, warning, critical
        'status', // unread, read, resolved
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reference()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }
}
