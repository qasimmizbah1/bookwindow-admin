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


}