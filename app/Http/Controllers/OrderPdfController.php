<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPdfController extends Controller
{
    public function download(Order $order)
    {
        $order->load('items.product');

        $pdf = Pdf::loadView('pdf.order', [
            'order' => $order
        ]);

        return $pdf->download(
            'order-' . $order->order_number . '.pdf'
        );
    }
    
    public function print(Order $order)
    {
        $order->load('items.product');

        return view('pdf.order', [
            'order' => $order,
            'is_print' => true
        ]);
    }
}