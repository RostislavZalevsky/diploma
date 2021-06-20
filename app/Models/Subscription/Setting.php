<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'value',
    ];

    public $timestamps = false;

    public static function getValueByKey($key) {
        if (!static::query()->where('key', '=', $key)->exists()) {
            return null;
        }

        return static::query()->where('key', '=', $key)->first()->value;
    }
}
