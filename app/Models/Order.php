<?php

namespace App\Models;

use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code', 
        'user_id', 
        'total_amount', 
        'voucher_id', 
        'order_status',  
        'confirmation_date'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_code = IdGenerator::generate([
                'table' => 'orders',
                'field' => 'order_code',
                'length' => 12,
                'prefix' => 'ORD' . Carbon::now()->format('y') . Carbon::now()->format('md'),
                'reset_on_prefix_change' => true,
            ]);
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function orderSchedule() {
        return $this->hasOne(OrderSchedule::class);
    }

    public function orderPayment() {
        return $this->hasOne(OrderPayment::class);
    }

    public function orderProducts() {
        return $this->hasMany(OrderProduct::class);
    }
}
