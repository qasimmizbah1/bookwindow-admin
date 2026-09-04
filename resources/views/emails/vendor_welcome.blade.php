<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to BookWindow - Vendor Application Received</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #0f172a;
            color: #ffffff;
            padding: 26px 24px;
            text-align: center;
        }
        .header img {
            max-width: 140px;
            max-height: 50px;
            margin-bottom: 12px;
            display: inline-block;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
        }
        .header p {
            margin: 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .content {
            padding: 24px;
        }
        .greeting {
            font-size: 16px;
            color: #0f172a;
            margin-bottom: 14px;
        }
        .intro-text {
            font-size: 14px;
            color: #475569;
            margin-bottom: 20px;
        }
        .status-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .status-row:last-child {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
            padding: 3px 10px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
        }
        .steps-container {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 16px 20px;
            border-radius: 4px;
            margin-bottom: 24px;
        }
        .steps-title {
            font-size: 14px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 10px;
        }
        .steps-list {
            margin: 0;
            padding-left: 20px;
            color: #1e293b;
            font-size: 13px;
        }
        .steps-list li {
            margin-bottom: 8px;
        }
        .support-box {
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #64748b;
        }
        .btn-container {
            text-align: center;
            margin: 24px 0 16px 0;
        }
        .btn {
            display: inline-block;
            background-color: #0284c7;
            color: #ffffff !important;
            padding: 12px 28px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);
        }
        .btn:hover {
            background-color: #0369a1;
        }
        .footer {
            background-color: #f8fafc;
            text-align: center;
            padding: 18px 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
        .footer p {
            margin: 4px 0;
        }
        .footer a {
            color: #0284c7;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="https://admin.bookwindow.in/storage/app/public/logo.png" alt="{{ config('app.name') }} Logo">
        <h1>Welcome to BookWindow!</h1>
        <p>Thank you for choosing to partner with us as a Seller</p>
    </div>

    <div class="content">
        <div class="greeting">
            Hello <strong>{{ $vendor->contact_person ?: $user->name }}</strong>,
        </div>

        <p class="intro-text">
            We are excited to receive your registration request for <strong>{{ $vendor->vendor_name }}</strong>. Your seller account details have been recorded successfully.
        </p>

        <div class="status-box">
            <div class="status-row">
                <span style="color: #64748b; font-weight: 600;">Store Name:</span>
                <span style="color: #0f172a; font-weight: 600;">{{ $vendor->vendor_name }}</span>
            </div>
            <div class="status-row">
                <span style="color: #64748b; font-weight: 600;">Registered Email:</span>
                <span style="color: #0f172a;">{{ $user->email }}</span>
            </div>
            <div class="status-row">
                <span style="color: #64748b; font-weight: 600;">Support Phone:</span>
                <span style="color: #0f172a;">{{ $vendor->vendor_phone }}</span>
            </div>
            <div class="status-row">
                <span style="color: #64748b; font-weight: 600;">Warehouse Location:</span>
                <span style="color: #0f172a;">{{ $vendor->city }}, {{ $vendor->state }} ({{ $vendor->pincode }})</span>
            </div>
            <div class="status-row">
                <span style="color: #64748b; font-weight: 600;">Application Status:</span>
                <span class="status-badge">● Pending Verification</span>
            </div>
        </div>

        <div class="steps-container">
            <div class="steps-title">What Happens Next?</div>
            <ol class="steps-list">
                <li><strong>Profile & KYC Verification:</strong> Our onboarding team will verify your submitted business details (PAN, GSTIN, and Bank Information) within <strong>24 to 48 business hours</strong>.</li>
                <li><strong>Account Approval:</strong> Once approved, you will receive an account activation notification and will be able to log in to the Seller Portal.</li>
                <li><strong>Start Selling:</strong> Easily list your book inventory, manage stock, and begin receiving orders directly from BookWindow readers.</li>
            </ol>
        </div>

        <div class="support-box">
            <strong>Need assistance or have questions?</strong><br>
            Our seller support team is always here to help. Feel free to contact us at 
            <a href="mailto:info@bookwindow.in" style="color: #0284c7; font-weight: 600;">info@bookwindow.in</a>.
        </div>

        <div class="btn-container">
            <a href="{{ config('app.frontend_url', 'https://bookwindow.in') }}" class="btn">Visit BookWindow</a>
        </div>

        <p style="font-size: 13px; color: #64748b; margin-top: 24px;">
            Best regards,<br>
            <strong>The BookWindow Seller Operations Team</strong>
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>
            <a href="{{ config('app.frontend_url', 'https://bookwindow.in') }}/privacy">Privacy Policy</a> | 
            <a href="{{ config('app.frontend_url', 'https://bookwindow.in') }}/terms">Terms & Conditions</a>
        </p>
        <p style="color: #94a3b8; font-size: 11px;">
            You received this email because an application for a vendor account was submitted using this email address.
        </p>
    </div>
</div>
</body>
</html>
