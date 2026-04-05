<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_order_id',
        'transaction_id',
        'payment_method',
        'status',
        'amount',
        'currency',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
    ];


    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }


    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
