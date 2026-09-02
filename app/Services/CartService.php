<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartService
{
    protected $cart;

    public function __construct()
    {
        $this->initCart();
    }

    protected function initCart()
    {


        if (Auth::check()) {

          
                $this->cart = Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                [
                'user_id' => Auth::id(),
                'session_id' => null, // Clear session ID if exists
                'created_at' => now(),
                'updated_at' => now()
                ]
                );

                // Optional: Merge any existing guest cart into user cart
                $guestCart = Cart::where('session_id', session()->get('cart_identifier'))->first();
                if ($guestCart && $guestCart->id !== $this->cart->id) {
                // Merge logic here (move items from guest cart to user cart)
                $this->mergeCarts($guestCart, $this->cart);
                $guestCart->delete();
                }

            // $this->cart = Cart::firstOrCreate([
            //     'user_id' => Auth::id()
            // ]);


        } else {

                    // For guests
            $cartIdentifier = session()->get('cart_identifier', Str::uuid()->toString());

            $sessionid =  session()->put('cart_identifier', $cartIdentifier);

            $this->cart = Cart::firstOrCreate(
                ['session_id' => $cartIdentifier],
                [
                    'session_id' => $cartIdentifier,
                    'user_id' => null, // Ensure user_id is null for guests
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );



            // $sessionId = session()->getId(); // Changed from custom session ID
            
            // $this->cart = Cart::firstOrCreate([
            //     'session_id' => $sessionId
            // ], [
            //     'session_id' => $sessionId,
            //     'created_at' => now(),
            //     'updated_at' => now()
            // ]);
            
            // // Debug output
            // \Log::debug('Guest cart initialized', [
            //     'session_id' => $sessionId,
            //     'cart_id' => $this->cart->id
            // ]);
        }
    }

    public function getCart()
    {
        return $this->cart->load('items.product');

    }

    public function addItem(Product $product, $quantity = 1)
    {
        $existingItem = $this->cart->items()->where('product_id', $product->id)->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity
            ]);
        } else {
            CartItem::create([
                'cart_id' => $this->cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price
            ]);
        }

        return $this->getCart();
    }

    public function updateItem($productId, $quantity)
    {
        $item = $this->cart->items()->where('product_id', $productId)->firstOrFail();
        
        if ($quantity <= 0) {
            $item->delete();
        } else {
            $item->update(['quantity' => $quantity]);
        }

        return $this->getCart();
    }

    public function removeItem($productId)
    {
        $this->cart->items()->where('product_id', $productId)->delete();
        return $this->getCart();
    }

    public function clearCart()
    {
        $this->cart->items()->delete();
        return $this->getCart();
    }

    public function getSubtotal()
    {
        return $this->cart->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function mergeGuestCartToUser($userId)
    {
        $guestCart = $this->cart;
        
        if ($guestCart->user_id === null) {
            $userCart = Cart::firstOrCreate(['user_id' => $userId]);
            
            foreach ($guestCart->items as $item) {
                $existingItem = $userCart->items()->where('product_id', $item->product_id)->first();
                
                if ($existingItem) {
                    $existingItem->update([
                        'quantity' => $existingItem->quantity + $item->quantity
                    ]);
                } else {
                    $userCart->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price
                    ]);
                }
            }
            
            $guestCart->items()->delete();
            $guestCart->delete();
            
            $this->cart = $userCart;
        }
        
        return $this->getCart();
    }
}