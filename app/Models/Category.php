<?php

namespace App\Models;

use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_code',
        'name',
        'description',
        'slug',
    ];

    protected $hidden = ['pivot'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->category_code = IdGenerator::generate([
                'table' => 'categories',
                'field' => 'category_code',
                'length' => 10,
                'prefix' => 'cid_' . STR::random(6),
                'reset_on_prefix_change' => true,
            ]);
        });
    }

    public function products() {
        return $this->belongsToMany(Product::class, 'product_categories', 'category_id', 'product_id');
    }
}
