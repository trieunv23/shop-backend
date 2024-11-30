<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductImage;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CodeHelper;

class ProductController extends Controller
{
    public function getProductsByCategory($filter) 
    {
        $category = null;

        if ($filter !== 'all') {
            $category = Category::where('slug', $filter)->first();

            if (!$category) {
                return response()->json([
                    'message' => 'category not found'
                ], 404);
            }

            $products = Product::with(['colors', 'sizes', 'firstProductImage']);

            $products = $products->whereHas('categories', function($query) use ($filter) {
                $query->where('slug', $filter);
            })->get();
        } else {
            $products = Product::with(['colors', 'sizes', 'firstProductImage'])->get();
        }

        return response()->json([
            'products' => $products,
            'category' => $category
        ]);
    }

    public function createProduct(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'images' => 'required|array',
            'price' => 'required|numeric',
            'description' => 'required|string|max:255', 
            'weight' => 'required|numeric',
            'categories' => 'required|array',
            'colors' => 'required|array',
            'sizes' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());

        $imagePath = array_map(function($image) use ($product) {
            return [
                'img_url' => $image->store('products', 'public'), 
                'product_id' => $product->id, 
                'created_at' => now(), 
                'updated_at' => now()
            ];
        }, $request->file('images'));

        ProductImage::insert($imagePath);

        $product->colors()->attach($request->colors); // attach: use for many-to-many relationship

        $product->sizes()->attach($request->sizes);

        $product->categories()->attach($request->categories);

        return response()->json([
            'request' => $request->all(),
        ], 201);
    }

    public function getProduct ($id)
    {
        $product = Product::with(['colors', 'sizes', 'categories', 'productImages'])->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product, 200);
    }

    public function getProducts($id)
    {
        // Tìm danh mục và tải trước các sản phẩm với mối quan hệ `colors`
        $category = Category::with(['products.colors'])->find($id);
        
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        // Lấy danh sách sản phẩm từ danh mục
        $products = $category->products;

        return response()->json($products, 200);
    }

    public function getAllProduct(Request $request) 
    {
        // Authentication Admin
        
        try {
            $products = Product::with('categories')->get();

            $productsArray = StringHelper::convertListKeysToCamelCase($products->toArray());

            return response()->json($productsArray);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }

}
