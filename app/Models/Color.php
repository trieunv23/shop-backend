<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function products() {
        return $this->belongsToMany(Product::class, 'product_colors', 'color_id', 'product_id');
        // 'product_colors': bảng trung gian
    }
}
