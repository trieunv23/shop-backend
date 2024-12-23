<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class ProductController extends Controller
{
    public function __construct() {
        $this->middleware('modify.response');
    }

    public function showProducts($filter) 
    {
        $category = null;

        $products = Product::with(['colors', 'sizes', 'image'])->get();

        if ($filter !== 'all') {
            $category = Category::where('slug', $filter)->first();

            if (!$category) {
                return response()->json([
                    'message' => 'category not found'
                ], 404);
            }

            $products = Product::with(['colors', 'sizes', 'image']);

            $products = $products->whereHas('categories', function($query) use ($filter) {
                $query->where('slug', $filter);
            })->get();
        }

        return response()->json([
            'products' => $products,
            'category' => $category
        ]);
    }

    public function showProduct($id)
    {
        $product = Product::with(['colors', 'sizes', 'categories', 'images'])->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'product' => $product
        ], 201);
    }
}
