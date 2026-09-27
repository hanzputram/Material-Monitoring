<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'group',
        'is_base_unit',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
    ];

    public function materials()
    {
        return $this->hasMany(Material::class, 'default_unit_id');
    }
}
