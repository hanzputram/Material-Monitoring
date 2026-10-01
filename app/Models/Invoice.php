<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'project_id',
        'purchase_order_id',
        'supplier_id',
        'invoice_number',
        'invoice_date',
        'amount',
        'attachment_path',
        'validated_by',
        'status',
        'notes',
        'down_payment_id',
        'down_payment_amount',
        'paid_amount',
        'payment_status',
        'due_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'down_payment_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function downPayment()
    {
        return $this->belongsTo(PurchaseDownPayment::class, 'down_payment_id');
    }

    public function payments()
    {
        return $this->belongsToMany(PurchasePayment::class, 'purchase_payment_items')
            ->withPivot('amount_paid')
            ->withTimestamps();
    }

    public function paymentItems()
    {
        return $this->hasMany(PurchasePaymentItem::class);
    }

    public function varianceValidations()
    {
        return $this->hasMany(MaterialVarianceValidation::class);
    }

    public function getNetAmountAttribute(): float
    {
        return max(0, (float) $this->amount - (float) ($this->down_payment_amount ?? 0));
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->net_amount - (float) ($this->paid_amount ?? 0));
    }

    public function recalculatePaymentStatus(): void
    {
        $totalPaid = (float) $this->paymentItems()->sum('amount_paid');
        $this->paid_amount = $totalPaid;

        if ($totalPaid >= $this->net_amount && $this->net_amount > 0) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }

        $this->save();
    }
}
