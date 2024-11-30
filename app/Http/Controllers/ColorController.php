<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function getColors (Request $request)
    {
        $colors = Color::all();

        return response()->json([
            'colors' => $colors   
        ]);
    }
}
