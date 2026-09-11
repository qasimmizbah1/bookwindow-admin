<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderApiController extends Controller
{
            public function show($orderNumber)
            {
            
            $order = DB::table('orders')
                ->where('order_number', $orderNumber)
                ->first();

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

           
            $items = DB::table('order_items')
                ->where('order_id', $order->id)
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'order_items.*',
                    'products.name as product_name',
                    'products.image as product_image'  // Assuming 'image' is the field in products table
                )
                ->get();

           
            $customer = DB::table('customers')
                ->where('id', $order->user_id)
                ->first();

            return response()->json([
                'data' => [
                    'order' => $order,
                    'items' => $items,
                    'customer' => $customer,
                ]
            ]);
            }

                    public function userOrders($user_id)
            {
            // Get all orders for the user
            $orders = DB::table('orders')
                ->where('user_id', $user_id)
                ->get();

            if ($orders->isEmpty()) {
                return response()->json(['message' => 'No orders found for this user'], 404);
            }

            $result = [];

            foreach ($orders as $order) {
                // Get items for each order
                $items = DB::table('order_items')
                    ->where('order_id', $order->id)
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->select(
                        'order_items.*',
                        'products.name as product_name',
                        'products.image as product_image'
                    )
                    ->get();

                // Add each order to the result array
                $result['orders'][] = [
                        
                        'order_details' => $order,
                        'items' => $items
                        
                    
                ];
            }

            return response()->json([
                'data' => $result
            ]);
            }

    public function cancelOrder(Request $request)
    {
        $request->validate([
            'order_number' => 'required',
            'reason' => 'required|string|max:500',
        ]);

        $user = auth('customer')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Please login to continue.'
            ], 401);
        }

        $order = Order::where('order_number', $request->order_number)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $currentStatus = strtolower(trim($order->status));

        // Shipping Guard: Cannot cancel if shipped, completed, or delivered
        if (in_array($currentStatus, ['order_shipped', 'shipped', 'completed', 'delivered'])) {
            return response()->json([
                'status' => false,
                'message' => 'Order cannot be cancelled because it has already been shipped or completed.'
            ], 422);
        }

        if (in_array($currentStatus, ['cancelled', 'declined'])) {
            return response()->json([
                'status' => false,
                'message' => 'This order is already ' . $currentStatus . '.'
            ], 422);
        }

        // Update order status
        $order->status = 'cancelled';
        $order->cancelled_by = 'customer';
        $order->cancellation_reason = $request->reason;
        $order->save();

        return response()->json([
            'status' => true,
            'message' => 'Order cancelled successfully.',
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'cancelled_by' => $order->cancelled_by,
                'cancellation_reason' => $order->cancellation_reason,
            ]
        ]);
    }

}