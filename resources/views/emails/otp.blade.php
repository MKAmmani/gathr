<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; background: #ffffff;">
    <div style="max-width: 520px; margin: 0 auto; padding: 24px;">
        <div style="text-align: center; margin-bottom: 24px;">
            <img src="{{ url('/logo.png') }}" alt="Gathr" style="height: 40px; width: auto;">
        </div>
        <h1 style="font-size: 20px; margin-bottom: 8px;">Verify your email</h1>
        <p style="margin: 0 0 16px;">Use this code to finish setting up your account.</p>
        <div style="font-size: 28px; font-weight: 700; letter-spacing: 6px; margin: 16px 0;">
            {{ $code }}
        </div>
        <p style="margin: 0;">This code expires in {{ $minutes }} minutes.</p>
        <p style="margin-top: 16px; color: #6b7280; font-size: 12px;">
            If you did not request this, you can ignore this email.
        </p>
    </div>
</body>
</html>
