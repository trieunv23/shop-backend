<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function showBanners()
    {
        $banners = Banner::orderBy('order', 'asc')->get();
        
        return response()->json([
            'banners' => $banners
        ], 200);
    }
}
