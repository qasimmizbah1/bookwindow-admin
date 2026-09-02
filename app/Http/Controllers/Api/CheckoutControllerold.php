<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'address' => 'required|string',
            'address_2' => 'nullable|string',
            'notes' => 'nullable|string',
            'coupon_code' => 'nullable|string'
        ]);

        return $this->processCheckout($request, Auth::id());
    }

    public function guestCheckout(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'shipping_method_id' => 'required|exists:shipping_methods,id',
            'address' => 'required|string',
            'address_2' => 'nullable|string',
            'notes' => 'nullable|string',
            'coupon_code' => 'nullable|string'
        ]);

        return $this->processCheckout($request, null, $request->email);
    }

    protected function processCheckout(Request $request, $userId = null, $email = null)
    {
        $cart = $this->cartService->getCart();
        $shippingMethod = ShippingMethod::findOrFail($request->shipping_method_id);
        
        // Calculate subtotal
        $subtotal = $this->cartService->getSubtotal();
        
        // Apply coupon if provided
        $discountAmount = 0;
        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_to', '>=', now())
                ->first();

            if ($coupon) {
                if ($coupon->type === 'fixed') {
                    $discountAmount = min($coupon->value, $subtotal);
                } else {
                    $discountAmount = $subtotal * ($coupon->value / 100);
                }
            }
        }
        
        // Calculate total
        $total = $subtotal - $discountAmount + $shippingMethod->price;
        
        // Create order
        $order = Order::create([
            'order_number' => 'ORD-' . Str::upper(Str::random(10)),
            'user_id' => $userId,
            'subtotal' => $subtotal,
            'shipping_amount' => $shippingMethod->price,
            'discount_amount' => $discountAmount,
            'total_amount' => $total,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'new',
            'shipping_method' => $shippingMethod->name,
            'shipping_address' => $request->address,
            'billing_address' => $request->address_2 ?? $request->address,
            'customer_email' => $email ?? Auth::user()->email,
            'notes' => $request->notes,
        ]);
        
        // Add order items
        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price
            ]);
        }
        
        // Clear cart
        $this->cartService->clearCart();
        
        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order
        ]);
    }
}