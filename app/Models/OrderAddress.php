<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'name',
        'phone_number',
        'province',
        'district',
        'ward',
        'detail',
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
