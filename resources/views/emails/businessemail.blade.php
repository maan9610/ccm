<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Business Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
        }
        .button:hover {
            background-color: #2c81ba;
        }
        .features {
            margin: 20px 0;
        }
        .features li {
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Welcome to Content Crown Media!</h1>
        </div>
        <p>Hi {{$name}},</p>
        <p>Thank you for registering with <strong>Content Crown Media</strong>! To get started and activate your business account, please verify your business email address.</p>
        <p style="text-align: center;">
            <a href="{{$verificationUrl}}" class="button">Verify Business Email Address</a>
        </p>
        <div class="features">
            <p><strong>Why verify?</strong></p>
            <ul>
                <li>✅ Secure your account</li>
                <li>✅ Enable full access to business features</li>
                <li>✅ Ensure timely communication</li>
            </ul>
        </div>
        <p>If you didn’t request this, you can safely ignore this email.</p>
        <p>For assistance, feel free to reach out to us at <a href="mailto:support@contentcrownmedia.com">support@contentcrownmedia.com</a>.</p>
        <p>Thank you for choosing <strong>Content Crown Media</strong>!</p>
        <div class="footer">
            <p>Best regards,<br>The Content Crown Media Team</p>
        </div>
    </div>
</body>
</html>
