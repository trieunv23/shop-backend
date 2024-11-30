<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function getSizes (Request $request)
    {
        $sizes = Size::all();

        return response()->json([
            'sizes' => $sizes   
        ]);
    }
}
