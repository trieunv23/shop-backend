<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'address_detail', 
        'province_id', 
        'district_id', 
        'ward_id', 
        'province_name', 
        'district_name', 
        'ward_name', 
        'customer_name', 
        'phone_number', 
        'is_default',
        'user_id'
    ];

    public function user() 
    {
        // One to One
        return $this->belongsTo(User::class);
    }
}
