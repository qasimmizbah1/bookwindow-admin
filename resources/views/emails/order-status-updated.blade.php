<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update - #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 20px;
            background-color: #f4f6f9;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .logo {
            max-width: 80px;
            height: auto;
        }
        .content {
            padding: 24px;
        }
        h1 {
            color: #1e293b;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 12px;
        }
        p {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: #475569;
        }
        /* Status Banner */
        .status-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 18px 0;
            display: block;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            border-radius: 12px;
            color: #ffffff;
        }
        .status-completed, .status-delivered { background-color: #16a34a; }
        .status-processing, .status-shipped { background-color: #2563eb; }
        .status-cancelled, .status-declined { background-color: #dc2626; }
        .status-pending { background-color: #d97706; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #f8fafc;
            padding: 10px 12px;
            text-align: left;
            font-size: 13px;
            color: #475569;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 13px;
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: block;
        }
        .product-name {
            font-weight: bold;
            color: #1e293b;
            font-size: 14px;
            margin-bottom: 3px;
        }
        .product-qty {
            color: #64748b;
            font-size: 12px;
        }

        /* Order Totals Table */
        .totals-table {
            width: 100%;
            margin-top: 15px;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 13px;
        }
        .totals-label {
            text-align: right;
            color: #64748b;
            width: 75%;
        }
        .totals-value {
            text-align: right;
            font-weight: 600;
            color: #1e293b;
            width: 25%;
        }
        .grand-total td {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #e2e8f0;
            padding-top: 8px;
        }

        .footer {
            text-align: center;
            padding: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #94a3b8;
            background-color: #f8fafc;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Logo Header -->
        <div class="header">
            <img src="https://admin.bookwindow.in/storage/app/public/logo.png" alt="{{ config('app.name') }}" class="logo">
        </div>
        
        <!-- Content -->
        <div class="content">
            <h1>Hi {{ $order->first_name ?? $order->customer_name ?? 'Customer' }},</h1>
            
            <p>Your order status has been updated. Here are the details of your order:</p>

            <!-- Status Banner -->
            @php
                $statusKey = strtolower($status);
                $statusClass = match($statusKey) {
                    'completed', 'delivered' => 'status-completed',
                    'processing', 'shipped' => 'status-processing',
                    'cancelled', 'declined' => 'status-cancelled',
                    default => 'status-pending'
                };
            @endphp
            <div class="status-box">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <span style="font-size: 13px; color: #475569;">Current Status:</span><br>
                            <span class="status-badge {{ $statusClass }}" style="margin-top: 4px;">{{ ucfirst($status) }}</span>
                        </td>
                        <td align="right" style="font-size: 13px; color: #64748b;">
                            <strong>Order #{{ $order->order_number }}</strong><br>
                            {{ $order->created_at ? $order->created_at->format('d M, Y') : now()->format('d M, Y') }}
                        </td>
                    </tr>
                    @if($statusKey === 'order_shipped' && !empty($order->tracking_id))
                    <tr>
                        <td align="left" style="font-size: 13px; color: #64748b;padding: 10px 0px;">
                            <strong>Tracking ID:</strong> {{ $order->tracking_id }}
                        </td>
                    </tr>
                     @endif
                </table>
            </div>

            <!-- Product Items Table -->
            <p style="font-weight: bold; color: #1e293b; margin-top: 20px; margin-bottom: 8px;">Order Items:</p>
            <table class="items-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="65">Image</th>
                        <th>Product Details</th>
                        <th width="80" style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($orderItems) && count($orderItems) > 0)
                        @foreach($orderItems as $item)
                            @php
                                $imgSrc = null;
                                $rawImage = $item->product_image ?? ($item->product->image ?? null);
                                if ($rawImage) {
                                    $imgSrc = str_starts_with($rawImage, 'http') ? $rawImage : rtrim(env('APP_URL'), '/') . '/storage/' . ltrim($rawImage, '/');
                                }
                            @endphp
                            <tr>
                                <td align="center">
                                    @if($imgSrc)
                                        <img src="{{ env('APP_URL') }}/storage/app/public/{{ $item->product_image }}" alt="{{ $item->product_name }}" class="product-img">
                                    @else
                                        <div style="width: 50px; height: 50px; background: #e2e8f0; border-radius: 4px; text-align: center; line-height: 50px; font-size: 10px; color: #94a3b8;">No Image</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="product-name">{{ $item->product_name ?? ($item->product->name ?? 'Book / Product') }}</div>
                                    <div class="product-qty">Qty: {{ $item->quantity }} &times; ₹{{ number_format($item->price, 2) }}</div>
                                    @if(!empty($item->courier_name) || !empty($item->tracking_number))
                                        <div style="margin-top: 4px; font-size: 11px; color: #2563eb;">
                                            <strong>Tracking:</strong> {{ $item->courier_name ?? 'Courier' }} - {{ $item->tracking_number }}
                                        </div>
                                    @endif
                                </td>
                                <td align="right" style="font-weight: bold; color: #1e293b;">
                                    ₹{{ number_format($item->price * $item->quantity, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>

            <!-- Order Financial Totals -->
            <table class="totals-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="totals-label">Subtotal:</td>
                    <td class="totals-value">₹{{ number_format($order->subtotal ?? 0, 2) }}</td>
                </tr>
                @if(($order->discount_amount ?? $order->discount ?? 0) > 0)
                <tr>
                    <td class="totals-label">Discount:</td>
                    <td class="totals-value" style="color: #16a34a;">-₹{{ number_format($order->discount_amount ?? $order->discount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td class="totals-label">Shipping:</td>
                    <td class="totals-value">₹{{ number_format($order->shipping_amount ?? 0, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td class="totals-label">Grand Total:</td>
                    <td class="totals-value">₹{{ number_format($order->total_amount ?? 0, 2) }}</td>
                </tr>
            </table>

            <p style="margin-top: 24px;">If you have any questions about your order, please reply to this email or contact us at <a href="mailto:info@bookwindow.in" style="color: #2563eb; text-decoration: none;">info@bookwindow.in</a>.</p>
            
            <p>Best regards,<br><strong>The {{ config('app.name') }} Team</strong></p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
           
        </div>
    </div>
</body>
</html>
