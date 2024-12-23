<?php

namespace App\Models;

use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name', 
        'price', 
        'description', 
        'category_id', 
        'is_active', 
        'weight', 
        'sold_out', 
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->code = IdGenerator::generate([
                'table' => 'products',
                'field' => 'code',
                'length' => 18,
                'prefix' => 'pid_' . Str::random(14),
                'reset_on_prefix_change' => true,
            ]);
        });
    }

    public function colors() {
        return $this->belongsToMany(Color::class, 'product_colors', 'product_id', 'color_id')
                    ->withTimestamps(); 
    }

    public function sizes() {
        return $this->belongsToMany(Size::class, 'product_sizes', 'product_id', 'size_id')
                    ->withTimestamps();
    }

    public function categories() {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id')
                    ->withTimestamps();
    }

    public function images() {
        return $this->hasMany(ProductImage::class);
    }

    public function image() {
        return $this->hasOne(ProductImage::class)->oldest('id');
    }

    public function cartProducts() {
        return $this->hasMany(CartProduct::class);
    }

    public function orderProduct() {
        return $this->hasMany(OrderProduct::class);
    }
}
