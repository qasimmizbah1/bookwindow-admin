<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPdfController extends Controller
{
    public function download(Order $order)
    {
        $order->load(['items.product', 'items.vendor']);

        $vendor = auth()->check() && auth()->user()->isVendor() ? auth()->user()->vendor : null;
        $items = $vendor ? $order->items->where('vendor_id', $vendor->id) : $order->items;

        $pdf = Pdf::loadView('pdf.order', [
            'order' => $order,
            'vendor' => $vendor,
            'items' => $items,
        ]);

        return $pdf->download(
            'order-' . $order->order_number . '.pdf'
        );
    }
    
    public function print(Order $order)
    {
        $order->load(['items.product', 'items.vendor']);

        $vendor = auth()->check() && auth()->user()->isVendor() ? auth()->user()->vendor : null;
        $items = $vendor ? $order->items->where('vendor_id', $vendor->id) : $order->items;

        return view('pdf.order', [
            'order' => $order,
            'vendor' => $vendor,
            'items' => $items,
            'is_print' => true
        ]);
    }
}