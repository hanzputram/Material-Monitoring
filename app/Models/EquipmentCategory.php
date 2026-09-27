<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentCategory extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(EquipmentCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(EquipmentCategory::class, 'parent_id');
    }

    public function equipment()
    {
        return $this->hasMany(EquipmentMaster::class);
    }
}
