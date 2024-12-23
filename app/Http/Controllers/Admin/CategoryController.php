<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct() {
        $this->middleware('modify.response');
    }

    public function showCategories(Request $request)
    {
        $categories = Category::withCount('products')->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function checkSlug(Request $request) 
    {
        try {
            $request->validate([ 'slug' => 'required|string|max:255', ]); 
            $slug = $request->input('slug'); 
            $slugExists = Category::where('slug', $slug)->exists(); 

            return response()->json(['isAvailable' => !$slugExists]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createCategory(Request $request) {
        try {
            $validated = $request->validate([ 
                'name' => 'required|string',
                'description' => 'required|string',
                'slug' => 'required|string|unique:categories,slug',
            ]);

            Category::create($validated);

            return response(['message' => 'Create category success.'], 200);
        } catch (\Exception $e) {
            return response(['error' => 'An error occurred.', 'message' => $e->getMessage()], 500);
        }
    }
}
