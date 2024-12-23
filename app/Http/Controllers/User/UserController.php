<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

use App\Helpers\StringHelper;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller 
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'refresh']]);
    }
}
