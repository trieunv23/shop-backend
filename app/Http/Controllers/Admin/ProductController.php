<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
        $this->middleware('modify.response');
    }
        
    public function showProducts() 
    {
        $products = Product::with(['colors', 'sizes', 'categories'])->get();

        $products = $products->map(function ($product) {
            $product->sizes->map(function ($size) {
                unset($size['created_at'], $size['updated_at']);

                return $size;
            });

            $product->colors->map(function ($color) {
                unset($color['created_at'], $color['updated_at']);

                return $color;
            });

            $product->categories->map(function ($category) {
                unset($category['created_at'], $category['updated_at']);

                return $category;
            });

            return $product;
        });

        return response()->json([
            'products' => $products
        ]);
    }

    public function showProduct($id) 
    {
        $product = Product::with(['colors', 'sizes', 'categories', 'images'])->findOrFail($id);

        return $product;
    }

    public function createProduct(Request $request) 
    {
        $rules = [
            'name' => 'required|string|max:255',
            'images' => 'required|array',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:2048',
            'price' => 'required|numeric',
            'description' => 'required|string|max:255', 
            'weight' => 'required|numeric',
            'categories' => 'required|array',
            'colors' => 'required|array',
            'sizes' => 'required|array',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $product = Product::create($request->all());

            $paths = [];
            foreach ($request->file('images') as $image) {
                $paths[] = [
                    'path' => $image->store('products', 'public'),
                    'product_id' => $product->id,
                ];
            }
            ProductImage::insert($paths);

            $product->colors()->attach($request['colors']);
            $product->sizes()->attach($request['sizes']);
            $product->categories()->attach($request['categories']);

            DB::commit();

            return response()->json([
                'request' => $request->all(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Product creation failed', 
                'error' => $e->getMessage(),
                'request' => $request->all()
            ], 500);
        }
    }

    public function updateProduct($id, Request $request)
    {
        $rules = [
            'name' => 'string|max:255',
            'images' => 'array',
            'images.*' => 'file|mimes:jpg,jpeg,png|max:2048',
            'image_ids' => 'array',
            'image_ids.*' => 'integer|exists:product_images,id',
            'price' => 'numeric',
            'description' => 'string|max:255', 
            'weight' => 'numeric',
            'categories' => 'array',
            'colors' => 'array',
            'sizes' => 'array',
            'sold_out' => 'boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            $product->update($request->only(['name', 'price', 'description', 'weight', 'sold_out']));

            if ($request->has('image_ids')) {
                $keepImageIds = $request->input('image_ids');

                $imagesToDelete = ProductImage::where('product_id', $product->id)
                    ->whereNotIn('id', $keepImageIds)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    Storage::disk('public')->delete($image->path);

                    $image->delete();
                }
            }

            if ($request->hasFile('images')) {
                $path = [];
                foreach ($request->file('images') as $image) {
                    $path[] = [
                        'path' => $image->store('products', 'public'),
                        'product_id' => $product->id,
                    ];
                }

                ProductImage::insert($path);
            }

            /* sync(): Áp dụng cho many to many -> dùng để đồng bộ các bản ghi ở bảng trung gian */

            if ($request->has('categories')) {
                $product->categories()->sync($request->input('categories'));
            }

            if ($request->has('colors')) {
                $product->colors()->sync($request->input('colors'));
            }

            if ($request->has('sizes')) {
                $product->sizes()->sync($request->input('sizes'));
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully.',
                'product' => $product->load(['images', 'categories', 'colors', 'sizes']),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

        return response()->json([
                'message' => 'Product update failed.',
            ], 500);
        }
    }

    public function deleteProduct($id) 
    {
        $product = Product::findOrFail($id);

        DB::beginTransaction();

        try {
            $productImages = $product->images;

            $product->delete();

            foreach ($productImages as $image) {
                Storage::disk('public')->delete($image->path);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Product delete failed',
            ], 500);
        }

        return response()->json([
            'message' => 'Product deleted successful'
        ], 200);
    }
}
