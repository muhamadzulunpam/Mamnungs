<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'payment_method', 'provider', 'transaction_id', 'amount', 'amount_received', 'change_amount', 'status', 'paid_at', 'raw_response'];
    protected $casts = ['paid_at' => 'datetime', 'raw_response' => 'array'];

    public function order() { return $this->belongsTo(Order::class); }
}
