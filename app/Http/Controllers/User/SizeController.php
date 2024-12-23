<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function showSizes()
    {
        $sizes = Size::all()->map(function ($size) {
            unset($size['created_at'], $size['updated_at']);

            return $size;
        });

        return response()->json([
            'sizes' => $sizes   
        ]);
    }
}
