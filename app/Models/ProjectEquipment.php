<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectEquipment extends Model
{
    protected $table = 'project_equipment';

    protected $fillable = [
        'project_id',
        'equipment_master_id',
        'qty',
        'source', // milik_sendiri, sewa
        'condition', // baru, layak_pakai, perlu_perbaikan
        'added_by',
        'notes',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function equipmentMaster()
    {
        return $this->belongsTo(EquipmentMaster::class, 'equipment_master_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
