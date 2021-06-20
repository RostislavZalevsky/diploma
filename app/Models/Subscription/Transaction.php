<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_transaction_id',
        'amount',
        'receipt',
        'payment_status',
        'status',
        'paid_at',
        'subscription_id'
    ];
}
