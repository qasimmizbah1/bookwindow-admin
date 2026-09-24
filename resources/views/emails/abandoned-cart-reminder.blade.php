<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Order - Bookwindow</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f6f8fa;
            margin: 0;
            padding: 20px;
            color: #1f2937;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
        }
        .header {
            background-color: #ffffff;
            padding: 24px 30px;
            text-align: center;
            border-bottom: 2px solid #ef4444;
        }
        .brand-name {
            font-size: 24px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.5px;
            text-decoration: none;
        }
        .brand-name span {
            color: #ef4444;
        }
        .content {
            padding: 30px;
        }
        h1 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-top: 0;
            margin-bottom: 12px;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #4b5563;
            margin: 0 0 16px 0;
        }
        .items-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin: 24px 0;
            background-color: #fafafa;
            overflow: hidden;
        }
        .item-row {
            display: flex;
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        .item-qty {
            font-size: 13px;
            color: #6b7280;
        }
        .item-price {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            text-align: right;
            padding-left: 12px;
        }
        .total-box {
            padding: 16px 20px;
            background-color: #f3f4f6;
            border-top: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }
        .cta-wrap {
            text-align: center;
            margin: 32px 0 24px 0;
        }
        .btn-recover {
            display: inline-block;
            background-color: #ef4444;
            color: #ffffff !important;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3);
        }
        .btn-recover:hover {
            background-color: #dc2626;
        }
        .note-box {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            color: #991b1b;
            margin-bottom: 20px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .footer a {
            color: #6b7280;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        @php
            $frontendUrl = config('app.frontend_url') ?: env('FRONTEND_URL', url('/'));
            $frontendHost = parse_url($frontendUrl, PHP_URL_HOST) ?: 'bookwindow.in';
            $appName = config('app.name', 'Bookwindow');
            $supportEmail = config('app.admin_email') ?: env('ADMIN_EMAIL', 'info@bookwindow.in');
        @endphp
        <div class="header">
            <a href="{{ $frontendUrl }}" class="brand-name">{{ $appName }}</a>
        </div>
        <div class="content">
            <div style="font-size: 15px; line-height: 1.6; color: #374151; margin-bottom: 24px;">
                {!! nl2br(e($messageBody)) !!}
            </div>

            <div class="items-card">
                @foreach($cart->items as $item)
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 1px solid #eeeeee; padding: 12px 16px;">
                        <tr>
                            <td style="vertical-align: top;">
                                <div class="item-name">{{ $item->product?->name ?? 'Book' }}</div>
                                <div class="item-qty">Quantity: {{ $item->quantity }}</div>
                            </td>
                            <td style="text-align: right; vertical-align: middle; white-space: nowrap; font-weight: 700; font-size: 14px; color: #111827;">
                                ₹{{ number_format(($item->price ?? ($item->product?->price ?? 0)) * $item->quantity, 2) }}
                            </td>
                        </tr>
                    </table>
                @endforeach
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 14px 16px;">
                    <tr>
                        <td style="font-weight: 700; font-size: 15px; color: #111827;">Estimated Cart Total:</td>
                        <td style="text-align: right; font-weight: 800; font-size: 16px; color: #ef4444;">
                            ₹{{ number_format($cart->calculateTotal(), 2) }}
                        </td>
                    </tr>
                </table>
            </div>

            <div class="cta-wrap">
                <a href="{{ $recoveryUrl }}" class="btn-recover">Return to My Cart &rarr;</a>
            </div>

            <p style="font-size: 13px; color: #6b7280; text-align: center;">
                Need help or have questions about books or delivery? Simply reply to this email or contact us at <a href="mailto:{{ $supportEmail }}" style="color: #ef4444;">{{ $supportEmail }}</a>.
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p><a href="{{ $frontendUrl }}">{{ $frontendHost }}</a> &bull; India's Premier Online Bookstore</p>
        </div>
    </div>
</body>
</html>
