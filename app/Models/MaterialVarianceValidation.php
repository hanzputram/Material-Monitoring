<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MaterialVarianceValidation extends Model
{
    protected $fillable = [
        'material_realization_id',
        'delivery_order_id',
        'invoice_id',
        'pengawas_id',
        'pengawas_validated_at',
        'pengawas_notes',
        'purchasing_id',
        'purchasing_validated_at',
        'purchasing_notes',
        'status',
    ];

    protected $casts = [
        'pengawas_validated_at' => 'datetime',
        'purchasing_validated_at' => 'datetime',
    ];

    public function realization()
    {
        return $this->belongsTo(MaterialRealization::class, 'material_realization_id');
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function pengawas()
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function pengawasUser()
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function purchasing()
    {
        return $this->belongsTo(User::class, 'purchasing_id');
    }

    public function purchasingUser()
    {
        return $this->belongsTo(User::class, 'purchasing_id');
    }

    public function validateByPengawas(int $userId, ?string $notes = null, ?int $deliveryOrderId = null): bool
    {
        $this->pengawas_id = $userId;
        $this->pengawas_validated_at = Carbon::now();
        $this->pengawas_notes = $notes;
        if ($deliveryOrderId) {
            $this->delivery_order_id = $deliveryOrderId;
            // Also validate the delivery order
            DeliveryOrder::where('id', $deliveryOrderId)->update(['status' => 'validated']);
        }

        if ($this->purchasing_validated_at !== null) {
            $this->status = 'fully_validated';
        } else {
            $this->status = 'waiting_purchasing';
        }

        $this->save();

        if ($this->status === 'fully_validated' && $this->realization) {
            MaterialRealization::recalculateForProjectMaterial(
                $this->realization->project_id,
                $this->realization->material_id
            );
        }

        return true;
    }

    public function validateByPurchasing(int $userId, ?string $notes = null, ?int $invoiceId = null): bool
    {
        $this->purchasing_id = $userId;
        $this->purchasing_validated_at = Carbon::now();
        $this->purchasing_notes = $notes;
        if ($invoiceId) {
            $this->invoice_id = $invoiceId;
            Invoice::where('id', $invoiceId)->update(['status' => 'validated']);
        }

        if ($this->pengawas_validated_at !== null) {
            $this->status = 'fully_validated';
        } else {
            $this->status = 'waiting_pengawas';
        }

        $this->save();

        if ($this->status === 'fully_validated' && $this->realization) {
            MaterialRealization::recalculateForProjectMaterial(
                $this->realization->project_id,
                $this->realization->material_id
            );
        }

        return true;
    }
}
