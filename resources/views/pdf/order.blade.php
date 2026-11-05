<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice - {{ $order->order_number }}</title>

<!-- Google Font for Browser Print Preview -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">

<style>
@font-face {
    font-family: 'Noto Sans Devanagari';
    font-style: normal;
    font-weight: normal;
    src: url('{{ str_replace('\\', '/', storage_path('fonts/NotoSansDevanagari-Regular.ttf')) }}') format('truetype');
}
@font-face {
    font-family: 'Noto Sans Devanagari';
    font-style: normal;
    font-weight: bold;
    src: url('{{ str_replace('\\', '/', storage_path('fonts/NotoSansDevanagari-Bold.ttf')) }}') format('truetype');
}

body {
    font-family: 'Noto Sans Devanagari', DejaVu Sans, sans-serif;
    font-size: 13px;
    color: #333;
    line-height: 1.4;
}

.header {
    text-align: center;
    padding-bottom: 8px;
    margin-bottom: 10px;
}

.order-meta-box {
    width: 100%;
    margin-bottom: 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
}

.order-meta-box td {
    border: none;
    padding: 8px 12px;
    vertical-align: middle;
}

.order-id-highlight {
    font-size: 15px;
    font-weight: bold;
    color: #111;
}

.logo {
    max-width: 100px;
    height: auto;
}

.details-section {
    width: 100%;
    margin-bottom: 20px;
}

.details-section td {
    vertical-align: top;
    border: none;
}

.details-section td.deliver-to {
    width: 50%;
    padding-right: 20px;
    padding-left: 0;
}

.details-section td.shipping-from {
    width: 50%;
    padding-left: 20px;
    padding-right: 0;
}

h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: normal;
    color: #555;
}

table {
    width: 100%;
    border-collapse: collapse;
}

.items-table {
    margin-top: 20px;
}

.items-table th {
    background: #f8f9fa;
    color: #333;
    font-weight: bold;
    text-align: center;
    border: 1px solid #ddd;
    padding: 10px;
}

.items-table td {
    border: 1px solid #ddd;
    padding: 10px;
}

.items-table .product-col {
    text-align: left;
}

.totals-table {
    width: 40%;
    float: right;
    margin-top: 10px;
    border-collapse: collapse;
}

.totals-table td {
    border: 1px solid #ddd;
    padding: 8px 10px;
}

.totals-table .label {
    text-align: right;
    font-weight: bold;
    background: #f8f9fa;
    width: 60%;
}

.totals-table .value {
    text-align: right;
}

.order-info {
    margin-bottom: 20px;
}

.bold {
    font-weight: bold;
}
@media print {
    @page {
        margin: 10mm;
    }

    html, body {
        margin: 0;
        padding: 0;
    }
}

</style>
</head>

<body>
<div class="header">
    @php
        $logoPath = storage_path('app/public/logo.png');
        $logoBase64 = '';
        if(file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
    @endphp
    @if($logoBase64)
        <img src="{{ $logoBase64 }}" class="logo" alt="BookWindow">
    @else
        <h2>BookWindow</h2>
    @endif
    <h3>
                @if(
                strtolower($order->payment_method ?? '') == 'cod' ||
                strtolower($order->payment_method ?? '') == 'cash on delivery' ||
                ($order->delivery_amount ?? 0) > 0
                )
                Collectable amount (COD): ₹{{ number_format($order->total_amount, 2) }}
                @else
                Collectable Amount: Paid
                @endif
            </h3>
</div>

<table class="order-meta-box">
    <tr>
        <td style="text-align: left;">
            <span class="order-id-highlight">ORDER ID: {{ $order->order_number }}</span>
        </td>
        <td style="text-align: right; font-size: 12px; color: #333;">
            <span class="bold">Invoice Date:</span> {{ now()->format('d-m-Y') }} &nbsp;|&nbsp;
            @php
                $pm = strtolower($order->payment_method ?? '');
                $paymentLabel = match($pm) {
                    'cod', 'cash on delivery' => 'COD',
                    'razorpay' => 'Prepaid (Razorpay)',
                    'card' => 'Prepaid (Card)',
                    '1' => 'Prepaid',
                    default => !empty($order->payment_method) ? Str::upper($order->payment_method) : 'N/A'
                };
            @endphp
            <span class="bold">Payment:</span> {{ $paymentLabel }}
            @if(!empty($order->courier_partner)) &nbsp;|&nbsp; <span class="bold">Courier:</span> {{ $order->courier_partner }}@endif
            @if(!empty($order->tracking_id)) &nbsp;|&nbsp; <span class="bold">Tracking:</span> {{ $order->tracking_id }}@endif
        </td>
    </tr>
</table>

<table class="details-section">
    <tr>
        <td class="deliver-to">
            <h4>Deliver to,</h4>
            <p style="margin:0;">
                <span class="bold">Name:</span> {{ $order->first_name }} {{ $order->last_name }}<br>
                <span class="bold">Address:</span> {{ $order->address }}<br>
                <span class="bold">City:</span> {{ $order->city }}<br>
                @if(!empty($order->district))<span class="bold">District:</span> {{ $order->district }}<br>@endif
                <span class="bold">State:</span> {{ $order->state }}<br>
                <span class="bold">Pin Code:</span> {{ $order->zip_code }}<br>
                <span class="bold">Phone:</span> {{ $order->formatted_phone ?? $order->customer_phone }}<br>
                <span class="bold">Email:</span> {{ $order->email }}
            </p>
        </td>
        
        <td class="shipping-from">
            <h4>Shipping From / Sold By,</h4>
            <p style="margin:0;">
                @if(isset($vendor) && $vendor)
                    <span class="bold">{{ $vendor->vendor_name }}</span><br>
                    @if($vendor->contact_person)<span class="bold">Contact:</span> {{ $vendor->contact_person }}<br>@endif
                    <span class="bold">Address:</span> {{ $vendor->vendor_address }}
                    @if($vendor->city || $vendor->state), {{ $vendor->city }} {{ $vendor->state }}@endif
                    @if($vendor->pincode) - {{ $vendor->pincode }}@endif<br>
                    <span class="bold">Phone:</span> {{ $vendor->vendor_phone ?? '+91 9468 888227' }}<br>
                    @if($vendor->gst_number)<span class="bold">GSTIN:</span> {{ $vendor->gst_number }}<br>@endif
                    @if($vendor->pan_number)<span class="bold">PAN:</span> {{ $vendor->pan_number }}<br>@endif
                    <span class="bold" style="color: #666; font-size: 11px;">(Fulfilled via BookWindow Platform)</span>
                @else
                    @php
                        $globalSetting = \App\Models\GlobalSetting::first();
                        $storeName = $globalSetting?->site_name ?? 'BOOKWINDOW';
                        $storeAddress = $globalSetting?->contact_address ?? "Shop No. 9, Udita tower, Radha Ballabh Marg, Dada Gurudev Nagar, Sanganer, Jaipur, Rajasthan 302029";
                        $storePhone = $globalSetting?->contact_phone ?? '+91 77425 66356';
                        $storeEmail = $globalSetting?->contact_email ?? 'info@bookwindow.in';
                    @endphp
                    <span class="bold">{{ $storeName }}</span><br>
                    <span class="bold">Store:</span> {!! nl2br(e($storeAddress)) !!}<br>
                    <span class="bold">Phone No:</span> {{ $storePhone }}<br>
                    <span class="bold">E-mail ID:</span> {{ $storeEmail }}<br>
                    <span class="bold">Website:</span> www.bookwindow.in
                @endif
            </p>
        </td>
    </tr>
</table>

@php
    $displayItems = isset($items) ? $items : $order->items;
    $rawItemsTotal = $displayItems->sum(function($item) {
        return ($item->quantity ?? 1) * ($item->price ?? 0);
    });
    $isLegacyDiscounted = (($order->discount_amount ?? 0) > 0 && $rawItemsTotal > 0 && abs(($rawItemsTotal + (float)$order->discount_amount) - (float)($order->subtotal ?? 0)) < 0.05);
    $subtotalRatio = ($isLegacyDiscounted && $rawItemsTotal > 0) ? ((float)$order->subtotal / $rawItemsTotal) : 1.0;
    $itemsTotal = ($order->subtotal && (float)$order->subtotal > 0) ? (float)$order->subtotal : $rawItemsTotal;
@endphp

<table class="items-table">
    <thead>
        <tr>
            <th width="5%">#</th>
            <th width="45%">Product</th>
            <th width="15%">Model</th>
            <th width="10%">Quantity</th>
            <th width="12%">Unit Price</th>
            <th width="13%">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($displayItems as $index => $item)
        @php
            $itemUnitPrice = $isLegacyDiscounted ? round(($item->price ?? 0) * $subtotalRatio, 2) : ($item->price ?? 0);
        @endphp
        <tr>
            <td align="center">{{ $loop->iteration }}</td>
            <td class="product-col">{{ $item->product?->name }}</td>
            <td align="center">{{ $item->product?->model ?? '-' }}</td>
            <td align="center" class="bold">{{ $item->quantity }}</td>
            <td align="center">₹{{ number_format($itemUnitPrice, 2) }}</td>
            <td align="center" class="bold">₹{{ number_format($item->quantity * $itemUnitPrice, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals-table">
    @if(isset($vendor) && $vendor)
    <tr>
        <td class="label">Total Items:</td>
        <td class="value">{{ $displayItems->sum('quantity') }}</td>
    </tr>
    <tr>
        <td class="label">Vendor Total:</td>
        <td class="value bold">₹{{ number_format($itemsTotal, 2) }}</td>
    </tr>
    @else
    <tr>
        <td class="label">Sub Total:</td>
        <td class="value">₹{{ number_format($order->subtotal, 2) }}</td>
    </tr>
    @if($order->discount_amount > 0)
    <tr>
        <td class="label">Discount:</td>
        <td class="value">-₹{{ number_format($order->discount_amount, 2) }}</td>
    </tr>
    @endif
    @if($order->tax_amount > 0)
    <tr>
        <td class="label">Tax:</td>
        <td class="value">₹{{ number_format($order->tax_amount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td class="label">Shipping Cost (Weight):</td>
        <td class="value">₹{{ number_format($order->shipping_amount, 2) }}</td>
    </tr>
    @if(strtolower($order->payment_method ?? '') == 'cod' || strtolower($order->payment_method ?? '') == 'cash on delivery' || ($order->delivery_amount ?? 0) > 0)
    <tr>
        <td class="label">COD:</td>
        <td class="value">₹{{ number_format($order->delivery_amount ?? 0, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td class="label">Total</td>
        <td class="value bold">₹{{ number_format($order->total_amount, 2) }}</td>
    </tr>
    @endif
</table>

<div style="clear:both"></div>

@if(isset($is_print) && $is_print)
<script>
    window.onload = function() {
        window.print();
        setTimeout(function() { window.close(); }, 500);
    }
</script>
@endif

</body>
</html>
