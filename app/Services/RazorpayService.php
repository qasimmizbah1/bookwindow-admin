<?php

namespace App\Services;

use Razorpay\Api\Api;
use App\Models\Order;

class RazorpayService
{
    protected $razorpay;

    public function __construct()
    {
        $this->razorpay = new Api(
            env('RAZORPAY_KEY'),
            env('RAZORPAY_SECRET')
        );
    }

    public function createOrder(Order $order, $receiptId)
    {
        return $this->razorpay->order->create([
            'receipt' => (string)$receiptId, // Convert to string explicitly
            'amount' => (int)round($order->total_amount * 100), // Ensure amount is an integer
            'currency' => 'INR',
            'payment_capture' => 1
        ]);
    }

    public function verifySignature($orderId, $paymentId, $signature)
    {
        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature
            ];

            $this->razorpay->utility->verifyPaymentSignature($attributes);
            return true;
        } catch (\Exception $e) {
            // Consider logging the error for debugging
            // logger()->error('Razorpay signature verification failed: ' . $e->getMessage());
            return false;
        }
    }
}