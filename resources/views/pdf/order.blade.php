<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice</title>

<style>
body{
    font-family: DejaVu Sans;
    font-size:12px;
    color:#333;
}

.header{
    border-bottom:2px solid #0f172a;
    padding-bottom:15px;
    margin-bottom:20px;
}

.logo{
    width:140px;
}

.company{
    text-align:right;
}

.section{
    margin-top:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#0f172a;
    color:#fff;
    padding:10px;
}

td{
    border:1px solid #ddd;
    padding:8px;
}

.total-table{
    width:40%;
    float:right;
    margin-top:20px;
}

.total-table td{
    border:none;
}

.grand{
    font-size:16px;
    font-weight:bold;
    border-top:2px solid #000;
}

.footer{
    margin-top:80px;
    text-align:center;
    color:#666;
}
</style>
</head>

<body>

<div class="header">

<table>
<tr>
<td width="50%">
<img src="{{ storage_path('app/public/logo.png') }}" class="logo">
</td>

<td width="50%" class="company">
<h2>INVOICE</h2>

<strong>Order #</strong>
{{ $order->order_number }}

<br>

<strong>Date:</strong>
{{ $order->created_at->format('d M Y') }}
<br>
<strong>Payment Method:</strong>
{{ Str::upper($order->payment_method) }}

</td>
</tr>
</table>

</div>

<div class="section">

<table>
<tr>

<td width="50%">

<h3>Customer Details</h3>

<strong>
{{ $order->first_name }}
{{ $order->last_name }}
</strong>

<br>

{{ $order->email }}

<br>

{{ $order->customer_phone }}

</td>

<td width="50%">

<h3>Shipping Address</h3>

{{ $order->address }}

<br>

{{ $order->city }}

<br>

{{ $order->state }}

<br>

{{ $order->country }}

<br>

{{ $order->zip_code }}

</td>

</tr>
</table>

</div>

<div class="section">

<h3>Order Items</h3>

<table>

<thead>
<tr>
<th>Product</th>
<th width="60">Qty</th>
<th width="100">Price</th>
<th width="120">Subtotal</th>
</tr>
</thead>

<tbody>

@foreach($order->items as $item)

<tr>

<td>
{{ $item->product?->name }}
</td>

<td align="center">
{{ $item->quantity }}
</td>

<td align="right">
₹{{ number_format($item->price,2) }}
</td>

<td align="right">
₹{{ number_format($item->quantity * $item->price,2) }}
</td>

</tr>

@endforeach

</tbody>

</table>

<table class="total-table">

<tr>
<td>Subtotal</td>
<td align="right">
₹{{ number_format($order->subtotal,2) }}
</td>
</tr>

<tr>
<td>Discount</td>
<td align="right">
-₹{{ number_format($order->discount_amount,2) }}
</td>
</tr>

<tr>
<td>Tax</td>
<td align="right">
₹{{ number_format($order->tax_amount,2) }}
</td>
</tr>

<tr>
<td>Shipping</td>
<td align="right">
₹{{ number_format($order->shipping_amount,2) }}
</td>
</tr>

<tr class="grand">
<td>Grand Total</td>
<td align="right">
₹{{ number_format($order->total_amount,2) }}
</td>
</tr>

</table>

</div>

<div style="clear:both"></div>

<div class="footer">

<p>
Thank you for shopping with BookWindow
</p>

<p>
www.bookwindow.in
</p>

</div>

</body>
</html>