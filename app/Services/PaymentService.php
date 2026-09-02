<?php

namespace App\Services;

use App\Models\Order;
//use Stripe\StripeClient;

class PaymentService
{
   // protected $stripe;

    public function __construct()
    {
        //$this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function processPayment(Order $order, $paymentMethod)
    {
        
            // COD doesn't need payment processing
            return null;
    

     
    }

    // protected function buildLineItems(Order $order)
    // {
    //     return $order->items->map(function ($item) {
    //         return [
    //             'price_data' => [
    //                 'currency' => 'usd',
    //                 'product_data' => [
    //                     'name' => $item->product->name,
    //                 ],
    //                 'unit_amount' => $item->price * 100,
    //             ],
    //             'quantity' => $item->quantity,
    //         ];
    //     })->toArray();
    // }
}