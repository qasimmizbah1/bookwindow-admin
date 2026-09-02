<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Get profile
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
