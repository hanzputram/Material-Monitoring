<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDownPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'supplier_id',
        'purchase_order_id',
        'dp_number',
        'dp_date',
        'amount',
        'payment_method',
        'bank_name',
        'status',
        'attachment_path',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'dp_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'down_payment_id');
    }
}
