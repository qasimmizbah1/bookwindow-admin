<?php 
// app/Http/Controllers/Api/CheckoutController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;
use App\Mail\AdminOrderNotification;
use App\Mail\VendorOrderNotification;
use App\Models\Vendor;
use App\Services\RazorpayService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        try {

        $sessionId = $request->session_id;
        DB::table('sessions')->upsert([
            'id' => $sessionId,
            'payload' => $sessionId,
            'last_activity' => 0
        ], ['id'], ['payload', 'last_activity']);

        $request->validate([
            'session_id' => 'required_if:is_guest,true|string|nullable',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => ['required', 'string', 'regex:/^(\+91[\-\s]?)?[0]?[6-9]\d{9}$/'],
            'shipping_method' => 'required|string',
            'address' => 'required|string',
            'address_2' => 'string|nullable',
            'zip_code' => ['required', 'regex:/^\d{6}$/'],
            'coupon_code' => 'nullable|string',
            'email' => 'required_if:is_guest,true|email|nullable',
            'is_guest' => 'sometimes|boolean',
        ], [
            'phone.required' => 'Mobile number is required.',
            'phone.regex' => 'Please enter a valid 10-digit mobile number.',
            'zip_code.required' => 'PIN code is required.',
            'zip_code.regex' => 'Please enter a valid 6-digit PIN code.',
        ]);

        $cleanPhone = preg_replace('/\D/', '', (string)$request->phone);
        if (str_starts_with($cleanPhone, '91') && strlen($cleanPhone) > 10) {
            $cleanPhone = substr($cleanPhone, 2);
        } elseif (str_starts_with($cleanPhone, '0') && strlen($cleanPhone) > 10) {
            $cleanPhone = substr($cleanPhone, 1);
        }

        if (strlen($cleanPhone) !== 10 || !preg_match('/^[6-9]\d{9}$/', $cleanPhone)) {
            return response()->json([
                'message' => 'Please provide a valid 10-digit mobile number.'
            ], 422);
        }

        $dbPhone = '+91' . $cleanPhone;

        // Get cart based on user or provided session ID
        $cart = $this->getCart($request);
        
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartItems = CartItem::with(['product' => function($query) {
        $query->select('*');
        }])->where('cart_id', $cart->id)
        ->get(['*']);
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty'], 400);
        }

        // Calculate subtotal
        $subtotal = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Process coupon if provided
        $discountAmount = $request->discount_amount ?? 0;
        $coupon = null;
        
        if ($request->coupon_code) {
           
            //     $coupon = DB::table('coupons')
            //     ->where('code', $request->coupon_code)
            //     ->where(function ($query) {
            //     $query->where('valid_from', '<=', now())
            //     ->orWhereNull('valid_from');
            //     })
            //     ->where(function ($query) {
            //     $query->where('valid_to', '>=', now())
            //     ->orWhereNull('valid_to');
            //     })
            //     ->where('is_active', true)
            //     ->first();
            
            // if ($coupon) {
            //     if (!$coupon->min_cart_amount || $subtotal >= $coupon->min_cart_amount) {
            //     $subtotal = DB::table('cart_items')
            //     ->where('cart_id', $cart->id)
            //     ->sum(DB::raw('price * quantity'));

            //     $newSubtotal = $subtotal;
            //     $discountAmount = 0;

                   

            //     if ($coupon->type === 'percent') {
            //      $discountAmount = $subtotal * ($coupon->value / 100);
            //     } elseif ($coupon->type === 'fixed') {
            //     $discountAmount = min($coupon->value, $subtotal); // Don't discount more than subtotal
            //     }
                
            //     // DB::table('carts')
            //     // ->where('id', $cartId)
            //     // ->update([
            //     // 'subtotal' => $newSubtotal,
            //     // 'discount_amount' => $discountAmount,
            //     // 'coupon_id' => $coupon->id,
            //     // 'total' => $newSubtotal // Assuming you might want to update total as well
            //     // ]);

            //    }
            // }
        }
            
         // Process shipping
        $shippingMethod = ShippingMethod::where('code', $request->shipping_method)->firstOrFail();

        $shippingAmount = $shippingMethod->price;

        if($shippingAmount>0)
        {

            $baseCost = 49;
            
            $totalItems = $cart->items() ->selectRaw('SUM(quantity) as total_items')->value('total_items');
            $shippingAmount = $shippingAmount * $totalItems;

        }
        $discountAmount = $request->discount_amount ?? 0;
        $totalAmount = $subtotal + $shippingAmount - $discountAmount;
        $user = $this->getOrCreateUser($request, $dbPhone);

        $order = Order::with('items')
        ->latest()
        ->firstOrFail();



        
         //Check payemnt method
        if ($request->payment_method === 'razorpay') {
        
        $order = Order::create([
            'session_id' => $request->session_id,
            'email' => $request->email,
            'user_id' => $user ? $user->id : null,
            'subtotal' => $subtotal,
            'shipping_amount' => $shippingAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $request->payment_method,
            'payment_status' => 'payment_pending',
            'address_2' => $request->address2,
            'shipping_method' => $shippingMethod->name,
            'address' => $request->address,
            'coupon_code' => $request->coupon_code,
            'status' => 'payment_pending',
            'customer_phone'=> $dbPhone,
            'first_name'=> $request->first_name,
            'last_name'=> $request->last_name,
            'zip_code' => $request->zip_code,
            'city' => $request->city,
            'state' => $request->state,
            'country' => 'India',
        ]);

        $order->update([
        'order_number' => 'OR-' . $order->id,
        ]);
       
        // Create order items
        foreach ($cartItems as $item) {

        $product_price = $item->price;

        $product_price = $this->calculateProductPriceWithCoupon($item, $request->coupon_code, $subtotal);
                
            DB::table('order_items')->insert([
            'order_id' => $order->id,
            'product_id' => $item->product_id,
            'product_name' => $item->product->name,
            'product_image' => $item->product->image,
            'price' => $product_price,
            'quantity' => $item->quantity,
            'product_weight' => $item->product->weight,
            'total' => $product_price * $item->quantity,
            'payment_method'=> $request->payment_method,
            'vendor_id' =>$item->product->vendor_id,
            'created_at' => now(),  // recommended to add timestamps if your table has them
            'updated_at' => now(),
            ]);

        }

        
            try {
                $razorpayService = new RazorpayService();
                $razorpayOrder = $razorpayService->createOrder($order, $order->order_number);

                $order->update([
                    'razorpay_order_id' => $razorpayOrder->id,
                    'payment_status' => 'created'
                ]);

                return response()->json([
                    'message' => 'Please complete payment',
                    'order' => $order,
                    'order_number' => $order->order_number,
                    'razorpay_order_id' => $razorpayOrder->id,
                    'razorpay_key' => config('services.razorpay.key') ?: env('RAZORPAY_KEY'),
                    'amount' => $totalAmount * 100,
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $request->email,
                    'contact' => $cleanPhone,
                    'keep_cart' => true, // Tell frontend to keep cart
                ]);
            } catch (\Exception $e) {
                \Log::error('Razorpay Order Creation Error: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'error' => 'Payment Gateway Error: ' . $e->getMessage() . '. Please verify Razorpay credentials or try Cash on Delivery.',
                    'message' => 'Payment Gateway Error: ' . $e->getMessage() . '. Please verify Razorpay credentials or try Cash on Delivery.'
                ], 500);
            }
        } else 
            {
            $totalAmount = $totalAmount + $request->delivery_amount; 

            $order = Order::create([
                
                'session_id' => $request->session_id,
                'email' => $request->email,
                'user_id' => $user ? $user->id : null,
                'subtotal' => $subtotal,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'address_2' => $request->address2,
                'shipping_method' => $shippingMethod->name,
                'address' => $request->address,
                'coupon_code' => $request->coupon_code,
                'status' => 'pending',
                'customer_phone'=> $dbPhone,
                'first_name'=> $request->first_name,
                'last_name'=> $request->last_name,
                'zip_code' => $request->zip_code,
                'city' => $request->city,
                'state' => $request->state,
                'country' => 'India',
                'delivery_amount'=>$request->delivery_amount,
            ]);

            $order->update([
            'order_number' => 'OR-' . $order->id,
            ]);
       
            
            foreach ($cartItems as $item) {

                $product_price = $this->calculateProductPriceWithCoupon($item, $request->coupon_code, $subtotal);
                
                DB::table('order_items')->insert([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_image' => $item->product->image,
                    'price' => $product_price,
                    'quantity' => $item->quantity,
                    'product_weight' => $item->product->weight,
                    'total' => $product_price * $item->quantity,
                    'payment_method' => $request->payment_method,
                    'vendor_id' => $item->product->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Clear cart only for non-Razorpay payments
            $this->clearCartAndSendEmails($cart, $order, $request);

            return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
            'order_number' => $order->order_number,
       
            ]);
        }
    }
     catch (\Exception $e) {
            logger()->error('Registration error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
}
    
    protected function getCart(Request $request)
    {
        if (Auth::check()) {
            // For logged-in users, get their cart
            return Cart::where('user_id', Auth::id())->first();
        } else {
            // For guests, get cart by provided session ID
            return Cart::where('session_id', $request->session_id)->first();
        }
    }

            protected function clearCartAndSendEmails($cart, $order, $request)
            {
            // Clear the cart
            CartItem::where('cart_id', $cart->id)->delete();
            $cart->delete();

            // Send emails
            $this->sendOrderEmails($order, $request);
            }


    protected function sendOrderEmails(Order $order, $request)
    {
        try {
            $customerEmail = $order->email ?? ($request ? $request->email : null);
            // 1. Send confirmation email to customer
            if ($customerEmail) {
                Mail::to($customerEmail)->send(new OrderConfirmation($order));
            }

            // 2. Send notification to admin
            $adminEmail = env('ADMIN_EMAIL');
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new AdminOrderNotification($order));
            }

            // 3. Send notification to each vendor who has items in this order
            $order->loadMissing(['items.product', 'items.vendor.user']);
            $vendorGroups = $order->items->whereNotNull('vendor_id')->groupBy('vendor_id');

            foreach ($vendorGroups as $vendorId => $vendorItems) {
                $vendor = $vendorItems->first()?->vendor ?? Vendor::with('user')->find($vendorId);
                $vendorEmail = $vendor?->user?->email;

                if ($vendor && $vendorEmail) {
                    Mail::to($vendorEmail)->send(new VendorOrderNotification($order, $vendor, $vendorItems));
                }
            }

        } catch (\Exception $e) {
            logger()->error('Email sending error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function getOrCreateUser(Request $request, $cleanPhone = null)
    {
        if (Auth::check()) {
            return Auth::customer();
        }

        if ($request->email) {
            // For guest checkout, find or create a user without password

            if($request->password)
            {
                $pass = bcrypt($request->password);
            }
            else
            {
                $pass =bcrypt(Str::random(10));
            }
           
            return $user = Customer::firstOrCreate(
                ['email' => $request->email],
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone'=> $cleanPhone ?? $request->phone,
                    'password' =>  $pass,
                    'address' => $request->address,
                    'address_2' => $request->address_2,
                    'zip_code' => $request->zip_code,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => 'India',
                ]
            );
        }

        return null;
    }
    public function showCouponCode(Request $request)
    {
        try {
            
            $coupon = DB::table('coupons')
                ->where('code', $request->coupon_code)
                ->where(function ($query) {
                $query->where('valid_from', '<=', now())
                ->orWhereNull('valid_from');
                })
                ->where(function ($query) {
                $query->where('valid_to', '>=', now())
                ->orWhereNull('valid_to');
                })
                ->where('is_active', true)
                ->first();

            if (empty($coupon)) {
            return response()->json(['message' => 'Invalid coupon code'], 422);
            }
           
            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => 'Coupon usage limit reached'], 422);
            }

            //$cart->update(['coupon_id' => $coupon->id]);
            //$coupon->increment('used_count');
            

            return $coupon;

        }
            catch (\Exception $e) {
            logger()->error('Email sending error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
            
    }

    public function getAvailableCoupons(Request $request)
    {
        try {
            $query = DB::table('coupons')
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('valid_from', '<=', now())
                        ->orWhereNull('valid_from');
                })
                ->where(function ($query) {
                    $query->where('valid_to', '>=', now())
                        ->orWhereNull('valid_to');
                });

            // Only check usage_limit if column exists
            if (Schema::hasColumn('coupons', 'usage_limit') && Schema::hasColumn('coupons', 'used_count')) {
                $query->where(function ($q) {
                    $q->whereNull('usage_limit')
                        ->orWhereRaw('used_count < usage_limit');
                });
            }

            $coupons = $query->select([
                    'id',
                    'code',
                    'type',
                    'value',
                    'category_id',
                    'min_cart_amount',
                    'max_cart_amount',
                    'valid_from',
                    'valid_to'
                ])
                ->orderBy('min_cart_amount', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'coupons' => $coupons
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching coupons: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch coupons',
                'error' => $e->getMessage(),
                'coupons' => []
            ], 500);
        }
    }

        //Cancel Payment
        public function handlePaymentCancel(Request $request)
        {
        $request->validate([
            'order_id' => 'required|numeric',
            'razorpay_order_id' => 'required|string'
        ]);


        $order = Order::where('id', $request->order_id)
                    ->where('razorpay_order_id', $request->razorpay_order_id)
                    ->first();

        if ($order) {
            $this->handleFailedPayment($order);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
        }

    // Add this new method to handle Razorpay callback
    public function razorpayCallback(Request $request)
    {

        try {
            
            $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required'
        ]);

            
            $razorpayService = new RazorpayService();


      

            //$order = Order::where('razorpay_order_id', $request->razorpay_order_id)->firstOrFail();
            $order = Order::with('items')
                ->where('razorpay_order_id', $request->razorpay_order_id)
                ->whereIn('payment_status', ['payment_pending', 'created'])
                ->firstOrFail();


            
        if ($razorpayService->verifySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        )) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'razorpay_payment_id' => $request->razorpay_payment_id
            ]);

            // Clear cart logic
            
            $this->sendOrderEmails($order, $request);
        
            
           // $this->clearCartAndSendEmails($this->getCartFromOrder($order), $order, $request);


            // if ($order->user_id) {
            //     $cart = Cart::where('user_id', $order->user_id)->first();
            // } else {
            $cart = Cart::where('session_id', $order->session_id)->first();
            // }

           if ($cart) {
             $this->clearCartAndSendEmails($cart, $order, $request);
             }


             return response()->json([
                'success' => true,
                'order' => $order,
                'cart' => $cart,
            ]);



             
         } 
         else {
            
            $this->handleFailedPayment($order);
            return response()->json(['success' => false, 'message' => 'Payment verification failed'], 400);
            
        }
           

        } catch (\Exception $e) {
            Log::error('Razorpay callback error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

        protected function calculateProductPriceWithCoupon($item, $couponCode, $subtotal)
        {
        $product_price = $item->price;

        if ($couponCode) {
        $coupon = DB::table('coupons')
            ->where('code', $couponCode)
            ->where(function ($query) {
                $query->where('valid_from', '<=', now())
                    ->orWhereNull('valid_from');
            })
            ->where(function ($query) {
                $query->where('valid_to', '>=', now())
                    ->orWhereNull('valid_to');
            })
            ->where('is_active', true)
            ->first();

        if ($coupon) {
            if (!$coupon->min_cart_amount || !$coupon->max_cart_amount || 
                ($subtotal >= $coupon->min_cart_amount && $subtotal <= $coupon->max_cart_amount)) {

                $couponCategoryIds = json_decode($coupon->category_id, true) ?? [];

                $product = DB::table('products')
                    ->where('id', $item->product_id)
                    ->where(function($query) use ($couponCategoryIds) {
                        $query->whereIn('category_id', $couponCategoryIds)
                            ->orWhereIn('sub_category_id', $couponCategoryIds);
                    })
                    ->first();

                if (!empty($coupon->category_id)) {
                    if ($product) {
                        if ($coupon->type === 'percent') {
                            $product_dis = $item->price * ($coupon->value / 100);
                        } elseif ($coupon->type === 'fixed') {
                            $product_dis = min($coupon->value, $item->price);
                        }
                        $product_price = $item->price - ($product_dis ?? 0);
                    } else {
                        $product_price = $item->price;
                    }
                } else {
                    if ($coupon->type === 'percent') {
                        $product_dis = $item->price * ($coupon->value / 100);
                    } elseif ($coupon->type === 'fixed') {
                        $product_dis = min($coupon->value, $item->price);
                    }
                    $product_price = $item->price - ($product_dis ?? 0);   
                }
            }
        }
        }

        return $product_price;
        }

        public function razorpayWebhook(Request $request)
    {
        // Handle asynchronous payment failures via webhook
        try {
            $webhookBody = $request->getContent();
            $webhookSignature = $request->header('X-Razorpay-Signature');
            
            $razorpayService = new RazorpayService();
            if ($razorpayService->validateWebhookSignature($webhookBody, $webhookSignature)) {
                $payload = json_decode($webhookBody, true);
                
                if ($payload['event'] === 'payment.failed') {
                    $paymentId = $payload['payload']['payment']['entity']['id'];
                    $order = Order::where('razorpay_payment_id', $paymentId)
                                 ->orWhere('razorpay_order_id', $payload['payload']['payment']['entity']['order_id'])
                                 ->first();
                    
                    if ($order && in_array($order->payment_status, ['payment_pending', 'created'])) {
                        $this->handleFailedPayment($order);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Razorpay webhook error:', ['error' => $e]);
        }
        
        return response()->json(['status' => 'ok']);
    }

    protected function handleFailedPayment(Order $order)
    {
        DB::transaction(function () use ($order) {
            // Option 1: Mark as failed (keeps record)
            $order->update([
                'payment_status' => 'payment_cancelled',
                'status' => 'payment_cancelled'
            ]);
            
            // Option 2: Or delete the order completely
            // $order->items()->delete();
            // $order->delete();
        });
    }


    protected function getCartFromOrder(Order $order)
    {
        if ($order->user_id) {
            return Cart::where('user_id', $order->user_id)->first();
        } else {
            return Cart::where('session_id', $order->session_id)->first();
        }
    }    
}