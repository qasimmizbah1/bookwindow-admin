<?php
namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        return response()->json(['cart' => $cart]);
    }

    public function add(Request $request)
    {


        try {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        if (!$request->session()->isStarted()) {
        $request->session()->start();
        }

            $token = $request->session()->token();

            $token = csrf_token();


             $product = Product::visibleToCustomers()->findOrFail($request->product_id);
                if (auth()->check()) {
                $cart = Cart::firstOrCreate(
                ['user_id' => auth()->id()],
                ['session_id' => $request->session_id]
                );
                $this->updateOrCreateCartItem($cart, $product, $request->quantity);

                // Also update session for consistency
                $this->updateSessionCart($product, $request->quantity);
                }
                else {
                // First try to find cart by session_id
                $cart = Cart::firstOrCreate(
                ['session_id' => $request->session_id],
                ['user_id' => null]
                );

                $this->updateOrCreateCartItem($cart, $product, $request->quantity);

                // Update session cart
                $this->updateSessionCart($product, $request->quantity);
                }
                return response()->json([
                'message' => 'Product added to cart',
                'cart' => $cart->load('items.product'),
                'total_products_count' => $cart->items->sum('quantity'), 
                'session_id' => $request->session_id // For debugging
                ])->withHeaders([
                'Access-Control-Allow-Credentials' => 'true'
                ]);

        // $product = Product::find($request->product_id);
        // $cart = session()->get('cart', []);

        // if (isset($cart[$product->id])) {
        //     $cart[$product->id]['quantity'] += $request->quantity;
        // } else {
        //     $cart[$product->id] = [
        //         'id'=> $product->id,
        //         'name' => $product->name,
        //         'price' => $product->price,
        //         'quantity' => $request->quantity,
        //     ];
        // }

        // session()->put('cart', $cart);

        // return response()->json(['message' => 'Product added to cart', 'cart' => $cart]);
            }
             catch (\Exception $e) {
            logger()->error('Registration error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

            protected function updateOrCreateCartItem($cart, $product, $quantity)
            {
            $cartItem = $cart->items()->where('product_id', $product->id)->first();

            if ($cartItem) {
                $cartItem->update([
                    'quantity' => $cartItem->quantity + $quantity
                ]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'image' => $product->image,
                    'product_weight' => $product->weight
                ]);
            }
            }

// Helper method to update session cart
protected function updateSessionCart($product, $quantity)
{
    $cart = session()->get('cart', []);
    
    if (isset($cart[$product->id])) {
        $cart[$product->id]['quantity'] += $quantity;
    } else {
        $cart[$product->id] = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $quantity,
            'image' => $product->image,
            'product_weight' => $product->weight,

        ];
    }
    
    session()->put('cart', $cart);
}

// Helper method to get cart data for response
protected function getCartData()
{
    if (auth()->check()) {
        $cart = Cart::with('items.product')->where('user_id', auth()->id())->first();
        return $cart ? $cart->toArray() : [];
    } else {
        return session('cart', []);
    }
}

   

    public function viewcart(Request $request)
{
    $sessionId = $request->session_id;
    if($sessionId){
     $cart = DB::table('carts')
        ->select('carts.*')
        ->where('carts.session_id', $sessionId)
        ->first();

    if (!$cart) {
        return null; // or create a new cart
    }

    $items = DB::table('cart_items')
        ->join('products', 'cart_items.product_id', '=', 'products.id')
        ->select(
            'cart_items.id',
            'cart_items.product_id',
            'products.name as product_name',
            'products.category_id as category_id',
            'products.sub_category_id as sub_category_id',
            'products.slug as product_slug',
            'products.price as product_price',
            'cart_items.quantity',
            'cart_items.image',
            'cart_items.product_weight',
            DB::raw('(cart_items.quantity * products.price) as subtotal'),
            'cart_items.created_at',
            'cart_items.updated_at'
        )
        ->where('cart_items.cart_id', $cart->id)
        ->get();

    // Calculate totals
    $total = $items->sum('subtotal');
    $itemsCount = $items->sum('quantity');

    return [
        'cart_id' => $cart->id,
        'session_id' => $cart->session_id,
        'items' => $items,
        'items_count' => $itemsCount,
        'total' => $total,
        'created_at' => $cart->created_at,
        'updated_at' => $cart->updated_at
    ];
}
else
{
    return response()->json([
        'message' => 'Cart items not found'
    ]);
}
    
}

 public function remove(Request $request)
    {
$sessionId = $request->session_id;
$productId = $request->product_id;
    $cart = Cart::where('session_id', $sessionId)->first();

if ($cart) {
    // Delete the item
    $deleted = $cart->items()->where('product_id', $productId)->delete();
    
    // Return appropriate response
    return response()->json([
        'success' => (bool)$deleted,
        'message' => $deleted ? 'Item removed' : 'Item not found'
    ]);
}

return response()->json(['success' => false, 'message' => 'Cart not found']);

    }

    public function empty(Request $request)
    {
        $sessionId = $request->session_id;
        $cart = DB::table('carts')
        ->where('session_id', $sessionId)
        ->delete();
        if (!$cart) {
        return null; // or create a new cart
        }
        return response()->json(['message' => 'Empty your Cart']);
    }

    public function cartupdate(Request $request)
    {
            $sessionId = $request->session_id;
            $productId = $request->product_id;
            $quantityChange = $request->quantity_change; // Expected to be +1 or -1

            // Validate the quantity change
            if (!in_array($quantityChange, [1, -1])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid quantity change value'
                ]);
            }

            $cart = Cart::where('session_id', $sessionId)->first();

            if ($cart) {
                // Find the cart item
                $cartItem = $cart->items()->where('product_id', $productId)->first();
                
                if ($cartItem) {
                    // Calculate new quantity
                    $newQuantity = $cartItem->quantity + $quantityChange;
                    
                    // Ensure quantity doesn't go below 1
                    if ($newQuantity < 1) {
                       $deleted = $cart->items()->where('product_id', $productId)->delete();
                    }
                    
                    // Update the quantity
                    $updated = $cartItem->update(['quantity' => $newQuantity]);
                    
                    // Return appropriate response
                    return response()->json([
                        'success' => (bool)$updated,
                        'message' => $updated ? 'Quantity updated' : 'Failed to update quantity',
                        'new_quantity' => $newQuantity
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found in cart'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Cart not found'
            ]);
    }
    

}
