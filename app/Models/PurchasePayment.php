<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'supplier_id',
        'payment_number',
        'payment_date',
        'payment_method',
        'bank_name',
        'total_amount',
        'attachment_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchasePaymentItem::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'purchase_payment_items')
            ->withPivot('amount_paid')
            ->withTimestamps();
    }
}
