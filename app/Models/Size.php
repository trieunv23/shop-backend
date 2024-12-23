<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected $hidden = ['pivot'];

    public function products() {
        return $this->belongsToMany(Product::class, 'product_sizes', 'product_id', 'size_id');
    }
}
