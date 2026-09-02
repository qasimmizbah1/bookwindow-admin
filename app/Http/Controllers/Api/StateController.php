<?php

// app/Http/Controllers/Api/StateController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StateResource;
use App\Models\State;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index()
    {
       $states = State::with(['cities' => function($query) {
        $query->where('is_active', true)->orderBy('name');
    }])
    ->where('is_active', true)
    ->orderBy('name')
    ->get();
        return $states;
    }

    
}