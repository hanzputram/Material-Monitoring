<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentMaster extends Model
{
    protected $table = 'equipment_master';

    protected $fillable = [
        'equipment_category_id',
        'code',
        'name',
        'default_unit_id',
        'price',
        'spec',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(EquipmentCategory::class, 'equipment_category_id');
    }

    public function defaultUnit()
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    public function projectEquipments()
    {
        return $this->hasMany(ProjectEquipment::class);
    }
}
