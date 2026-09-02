<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Ensure the session is started
            if (!$request->hasSession()) {
                $request->setLaravelSession(app('session')->driver());
            }

            $token = $request->session()->token();

            $token = csrf_token();

            // Get and increment count
            $count = session()->get('count', 0);
            $count++;
            session()->put('count', $count);

            return response()->json([
                'session_id' => session()->getId(),
                'count' => $count
            ]);
        } catch (\Exception $e) {
            logger()->error('Session error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
