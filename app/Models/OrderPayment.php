<?php

namespace App\Models;

use Carbon\Carbon;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderPayment extends Model
{
    use HasFactory;

    protected $table = 'order_payments';

    protected $fillable = [ 
        'order_id', 
        'payment_method', 
        'payment_status', 
        'payment_amount', 
        'payment_date', 
        'transaction_details',
        'payment_image',
        'payment_code' 
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($order_payment) {
            $order_payment->payment_code = IdGenerator::generate([
                'table' => 'order_payments',
                'field' => 'payment_code',
                'length' => 8,
                'prefix' =>  STR::random(8),
                'reset_on_prefix_change' => true,
            ]);
        });
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }
}
