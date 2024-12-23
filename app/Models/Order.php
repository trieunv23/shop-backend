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
        'code', 
        'user_id', 
        'total_amount', 
        'status',  
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->code = IdGenerator::generate([
                'table' => 'orders',
                'field' => 'code',
                'length' => 12,
                'prefix' => 'ORD' . Carbon::now()->format('y') . Carbon::now()->format('md'),
                'reset_on_prefix_change' => true,
            ]);
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function orderPayment() {
        return $this->hasOne(OrderPayment::class);
    }

    public function orderProducts() {
        return $this->hasMany(OrderProduct::class);
    }

    public function orderStatuses() {
        return $this->hasMany(OrderStatus::class);
    }

    public function orderAddress() {
        return $this->hasOne(OrderAddress::class);
    }
}
