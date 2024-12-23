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
        'method', 
        'status', 
        'total_amount', 
        'date', 
        'transaction_id',
        'receipt_url',
        'code',
        'notes',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($order_payment) {
            $order_payment->code = IdGenerator::generate([
                'table' => 'order_payments',
                'field' => 'code',
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
