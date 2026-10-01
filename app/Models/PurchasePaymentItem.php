<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_payment_id',
        'invoice_id',
        'amount_paid',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    public function purchasePayment()
    {
        return $this->belongsTo(PurchasePayment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
