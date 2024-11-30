<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'order_id', 
        'user_id', 
        'status', 
        'order_date', 
        'delivered_date', 
        'shipping_address', 
        'recipient_name', 
        'recipient_phone', 
        'shipping_cost', 
        'schedule_description', 
        'notes' 
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
