<!DOCTYPE html>
<html>
<head>
    <title>Contact Form Submission</title>
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
    </style>
</head>
<body>
     <div class="email-container">
    <div class="header">
    <img src="https://admin.bookwindow.in/storage/app/public/logo.png" alt="{{ config('app.name') }} Logo" class="logo">
    </div>
    <div class="content">
    <h1>Contact Form Submission</h1>
    <p>You’ve received a new message via the {{ config('app.name') }} contact form:</p>

    <p><strong>Name:</strong> {{ $firstName }} {{ $lastName }}</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Subject:</strong> {{ $subject }}</p>
    <p><strong>Message:</strong></p>
    <p>{{ $emailMessage }}</p>
    
    <p>Thank you</p>
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