<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'name',
        'type',
        'price',
    ];

    public function order() : BelongsTo {
        return $this->belongsTo(Order::class);
    }
}
