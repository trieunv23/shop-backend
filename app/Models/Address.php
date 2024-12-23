<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name', 
        'phone_number', 
        'province', 
        'district', 
        'ward', 
        'detail', 
        'is_default',
    ];

    public function user() 
    {
        // One to One
        return $this->belongsTo(User::class);
    }
}
