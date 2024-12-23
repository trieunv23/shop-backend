<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory;

    protected $fillable = [ 
        'order_id', 
        'status', 
        'previous_status', 
        'date', 
        'updated_by',
        'notes',
        'is_rollback',
        'is_rolled_back'
    ];
}
