<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialUnitConversion extends Model
{
    protected $fillable = [
        'material_id',
        'from_unit_id',
        'to_unit_id',
        'factor',
    ];

    protected $casts = [
        'factor' => 'decimal:6',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }
}
