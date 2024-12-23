<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function showColors()
    {
        $colors = Color::all()->map(function ($color) {
            unset($color['created_at'], $color['updated_at']);

            return $color;
        });

        return response()->json([
            'colors' => $colors   
        ]);
    }
}
