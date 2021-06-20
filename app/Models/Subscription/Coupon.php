<?php

namespace App\Models\Subscription;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'off',
        'discount',
        'duration',
        'repeat_count',
        'expired_at'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot(['subscription_id', 'created_at']);
    }
}
