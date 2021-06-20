<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'status',
        'payment_status'
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
