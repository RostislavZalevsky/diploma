<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'product_id', 'data'
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public static function getIdByName($name) {
        return static::getByName($name)->id;
    }

    public static function getByName($name) {
        return static::query()->where('name', '=', $name)->first();
    }
}
