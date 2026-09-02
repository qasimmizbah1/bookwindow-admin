<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPdfController extends Controller
{
    public function download(Order $order)
    {
        $order->load(['items.product', 'items.vendor']);

        $vendor = null;
        if (auth()->check() && auth()->user()->isVendor()) {
            $vendor = auth()->user()->vendor;
            $items = $vendor ? $order->items->where('vendor_id', $vendor->id) : $order->items;
        } else {
            $items = $order->items;
            // If all items belong to a single vendor, use that vendor for seller details
            $firstVendor = $order->items->first()?->vendor;
            if ($firstVendor && $order->items->every(fn ($item) => $item->vendor_id === $firstVendor->id)) {
                $vendor = $firstVendor;
            }
        }

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

        $vendor = null;
        if (auth()->check() && auth()->user()->isVendor()) {
            $vendor = auth()->user()->vendor;
            $items = $vendor ? $order->items->where('vendor_id', $vendor->id) : $order->items;
        } else {
            $items = $order->items;
            $firstVendor = $order->items->first()?->vendor;
            if ($firstVendor && $order->items->every(fn ($item) => $item->vendor_id === $firstVendor->id)) {
                $vendor = $firstVendor;
            }
        }

        return view('pdf.order', [
            'order' => $order,
            'vendor' => $vendor,
            'items' => $items,
            'is_print' => true
        ]);
    }
}