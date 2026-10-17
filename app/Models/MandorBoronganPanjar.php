<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MandorBoronganPanjar extends Model
{
    use HasFactory;

    protected $table = 'mandor_borongan_panjars';

    protected $fillable = [
        'mandor_borongan_id',
        'amount',
        'panjar_date',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'panjar_date' => 'date',
    ];

    public function mandorBorongan(): BelongsTo
    {
        return $this->belongsTo(MandorBorongan::class, 'mandor_borongan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
