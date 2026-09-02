<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice - {{ $order->order_number }}</title>

<style>
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 13px;
    color: #333;
    line-height: 1.4;
}

.header {
    text-align: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 20px;
    margin-bottom: 20px;
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
    padding: 0;
    border: none;
}

.deliver-to {
    width: 50%;
    padding-right: 20px;
}

.shipping-from {
    width: 50%;
    padding-left: 20px;
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
</div>

<table class="details-section">
    <tr>
        <td class="deliver-to">
            <h4>Deliver to,</h4>
            <p style="margin:0;">
                <span class="bold">Name:</span> {{ $order->first_name }} {{ $order->last_name }}<br>
                <span class="bold">Address:</span> {{ $order->address }}<br>
                <span class="bold">City:</span> {{ $order->city }}<br>
                <span class="bold">State:</span> {{ $order->state }}<br>
                <span class="bold">Pin Code:</span> {{ $order->zip_code }}<br>
                <span class="bold">Phone:</span> {{ $order->customer_phone }}<br>
                <span class="bold">Email:</span> {{ $order->email }}
            </p>
        </td>
        
        <td class="shipping-from">
            <h4>Shipping From,</h4>
            <p style="margin:0;">
                <span class="bold">BOOKWINDOW</span><br>
                <span class="bold">Store:</span> Shop No. 8, Maharani Garden road near by Hotel<br>
                Dwarika Palace, Mangyawas, Jaipur, 302020, Rajasthan<br>
                <span class="bold">Code:</span> 302020<br>
                <span class="bold">Phone No:</span> +91 9468 888227<br>
                <span class="bold">E-mail ID:</span> info@bookwindow.in<br>
                <span class="bold">Website:</span> www.bookwindow.in
            </p>
        </td>
    </tr>
</table>

<div class="order-info">
    <p style="margin:0;">
        Order Details,<br><br>
        <span class="bold">Invoice Date:</span> {{ now()->format('d-m-Y') }}<br>
        <span class="bold">Order ID:</span> {{ $order->order_number }}<br>
        <span class="bold">Payment Method:</span> ₹ / {{ Str::upper($order->payment_method) }}
    </p>
</div>

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
        @foreach($order->items as $index => $item)
        <tr>
            <td align="center">{{ $index + 1 }}</td>
            <td class="product-col">{{ $item->product?->name }}</td>
            <td align="center">{{ $item->product?->model ?? '-' }}</td>
            <td align="center" class="bold">{{ $item->quantity }}</td>
            <td align="center">₹{{ number_format($item->price, 2) }}</td>
            <td align="center" class="bold">₹{{ number_format($item->quantity * $item->price, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="totals-table">
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
    @if(strtolower($order->payment_method) == 'cod' || strtolower($order->payment_method) == 'cash on delivery')
    <tr>
        <td class="label">Cash On Delivery:</td>
        <td class="value">₹0.00</td>
    </tr>
    @endif
    <tr>
        <td class="label">Total</td>
        <td class="value bold">₹{{ number_format($order->total_amount, 2) }}</td>
    </tr>
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