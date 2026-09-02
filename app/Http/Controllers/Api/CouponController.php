<?php
// app/Http/Controllers/Api/CouponController.php

use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $cart = (new CartController)->getCart($request);

        dd($request->code);

        $coupon = Coupon::where('code', $request->code)
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now())
            ->first();



        if (empty($coupon)) {
            return response()->json(['message' => 'Invalid coupon code'], 422);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => 'Coupon usage limit reached'], 422);
        }

        $cart->update(['coupon_id' => $coupon->id]);
        $coupon->increment('used_count');

        return (new CartController)->show($request);
    }

    public function remove(Request $request)
    {
        $cart = (new CartController)->getCart($request);
        $cart->update(['coupon_id' => null]);
        
        return (new CartController)->show($request);
    }
}