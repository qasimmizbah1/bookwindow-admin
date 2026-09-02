<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        $cart = $this->cartService->getCart();
        return response()->json([
            'cart' => $cart,
            'subtotal' => $this->cartService->getSubtotal()
        ]);
    }

    public function store(Request $request)
    {
                $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1'
                ]);

                \Log::debug('Add to cart request', $request->all());

                $product = Product::findOrFail($request->product_id);

                \Log::debug('Product found', [
                'product_id' => $product->id,
                'price' => $product->price
                ]);

                $cart = $this->cartService->addItem($product, $request->quantity);

                \Log::debug('Cart after add', [
                'cart' => $cart->toArray(),
                'items_count' => $cart->items->count()
                ]);

                return $this->index();
    }

    public function update(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $this->cartService->updateItem($productId, $request->quantity);

        return $this->index();
    }

    public function destroy($productId)
    {
        $this->cartService->removeItem($productId);
        return $this->index();
    }
}