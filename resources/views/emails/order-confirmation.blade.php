<!DOCTYPE html>
<html>
<head>
    <title>Thank You for Your Order</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333333;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
            margin: 20px;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            width: 600px;
            margin: 20px auto;
            box-shadow: 5px 5px 5px 14px #ddd;
            border:1px solid #ddd;
            text-align: left;
        }
        .header {
            background-color: #ddd;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eeeeee;
        }
        .logo {
            max-width: 75px;
            height: auto;
        }
        .content {
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 10px;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 10px 0;
            border-top: 1px solid #eeeeee;
            font-size: 12px;
            color: #777777;
            background-color: #f5f5f5;
        }
        .social-icons {
            margin: 20px 0;
        }
        .social-icons a {
            margin: 0 10px;
            text-decoration: none;
        }
        .label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            width: 160px;
        }
        .message-box {
            background-color: #f5f5f5;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            font-style: italic;
        }
        .order-summary {
            margin: 20px 0;
            border: 1px solid #eee;
            border-radius: 5px;
            overflow: hidden;
        }
        .order-summary-header {
            background-color: #f9f9f9;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
        }
        .order-item {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 4px;
        }
        .product-details {
            flex-grow: 1;
        }
        .product-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .product-price {
            color: #2c3e50;
        }
        .product-quantity {
            color: #777;
        }
        .order-totals {
            margin-top: 20px;
            text-align: right;
            padding: 15px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .total-label {
            font-weight: bold;
        }
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="https://admin.bookwindow.in/storage/app/public/logo.png" alt="{{ config('app.name') }} Logo" class="logo">
        </div>
        
        <div class="content">
            <h1>Hi {{ $order->customer_name }},</h1>
            
            <p>Thank you for your order with <strong>{{ config('app.name') }}</strong>. We're processing your order and will notify you once it's shipped.</p>
            
            <p>Here's your order confirmation:</p>
            
            <div class="order-summary">
                <div class="order-summary-header">
                    Order #{{ $order->order_number }} (Placed on {{ $order->created_at->format('M d, Y') }})
                </div>
                
                @foreach($orderItems as $item)
                <div class="order-item">
                    <img src="{{ env('APP_URL') }}/storage/app/public/{{ $item->product_image }}" alt="{{ $item->product_title }}" class="product-image">
                    <div class="product-details">
                        <div class="product-title">{{ $item->product_name }}</div>
                        <div class="product-price">₹{{ number_format($item->price, 2) }}</div>
                        <div class="product-quantity">Qty: {{ $item->quantity }}</div>
                    </div>
                    <div class="product-total">₹{{ number_format($item->price * $item->quantity, 2) }}</div>
                </div>
                @endforeach
                
                <div class="order-totals">
                    <div class="total-row">
                        <span class="total-label">Subtotal:</span>
                        <span>₹{{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount > 0)
                    <div class="total-row">
                        <span class="total-label">Discount:</span>
                        <span>-₹{{ number_format($order->discount, 2) }}</span>
                    </div>
                    @endif
                    <div class="total-row">
                        <span class="total-label">Shipping:</span>
                        <span>₹{{ number_format($order->shipping_amount, 2) }}</span>
                    </div>
                    @if($order->tax_amount > 0)
                    <div class="total-row">
                        <span class="total-label">Tax:</span>
                        <span>₹{{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="total-row grand-total">
                        <span class="total-label">Total:</span>
                        <span>₹{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
            
            <p>We'll send you another email when your order ships. If you have any questions about your order, please reply to this email or contact us at <a href="mailto:info@bookwindow.in">info@bookwindow.in</a>.</p>
            
            <p>Thank you for shopping with us!</p>
            
            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href="{{ config('app.frontend_url') }}/privacy">Privacy Policy</a> | 
                <a href="{{ config('app.frontend_url') }}/terms">Terms of Service</a>
            </p>
        </div>
    </div>
</body>
</html>