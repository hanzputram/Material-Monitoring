<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'default_unit_id',
        'standard_price',
        'is_active',
    ];

    protected $casts = [
        'standard_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function defaultUnit()
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    public function conversions()
    {
        return $this->hasMany(MaterialUnitConversion::class);
    }

    public function rabItemMaterials()
    {
        return $this->hasMany(RabItemMaterial::class);
    }

    public function realizations()
    {
        return $this->hasMany(MaterialRealization::class);
    }
}
