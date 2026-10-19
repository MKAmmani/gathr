<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #F0F4F8;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            text-size-adjust: none;
        }
        .wrapper {
            width: 100%;
            background-color: #F0F4F8;
            padding: 40px 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 77, 128, 0.08), 0 8px 10px -6px rgba(0, 77, 128, 0.04);
        }
        .header {
            background: linear-gradient(135deg, #004D80 0%, #0096E3 100%);
            padding: 40px 32px 36px;
            text-align: center;
            color: #ffffff;
        }
        .header-logo {
            height: 42px;
            width: auto;
            margin-bottom: 20px;
            display: inline-block;
        }
        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 9999px;
            margin-bottom: 14px;
        }
        .header-title {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.3;
            letter-spacing: -0.02em;
        }
        .content {
            padding: 36px 32px 40px;
            color: #1E293B;
            font-size: 15px;
            line-height: 1.7;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0F172A;
            margin: 0 0 18px 0;
        }
        .message-body {
            color: #334155;
            font-size: 15px;
            line-height: 1.75;
            white-space: pre-line;
            margin-bottom: 30px;
        }
        .cta-container {
            text-align: center;
            margin: 32px 0 16px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #0096E3 0%, #007BBF 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 36px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 0 4px 14px rgba(0, 150, 227, 0.35);
        }
        .footer {
            background: #F8FAFC;
            padding: 28px 32px;
            text-align: center;
            color: #64748B;
            font-size: 12px;
            line-height: 1.6;
            border-top: 1px solid #E2E8F0;
        }
        .footer a {
            color: #0096E3;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <img src="{{ url('/logo.png') }}" alt="Gathr" class="header-logo">
                <div>
                    <span class="badge">{{ $badge ?? 'Announcement' }}</span>
                </div>
                <h1 class="header-title">{{ $subject }}</h1>
            </div>

            <!-- Content -->
            <div class="content">
                @if(!empty($recipientName))
                    <p class="greeting">Hello {{ $recipientName }},</p>
                @else
                    <p class="greeting">Hello Gathr Member,</p>
                @endif

                <div class="message-body">{!! nl2br(e($messageContent)) !!}</div>

                @if(!empty($actionUrl) && !empty($actionText))
                    <div class="cta-container">
                        <a href="{{ $actionUrl }}" class="cta-button" target="_blank">{{ $actionText }}</a>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer">
                <p style="margin: 0 0 8px;"><strong>Gathr</strong> — Smart group collections made simple.</p>
                <p style="margin: 0 0 8px;">You are receiving this official announcement because you are an active member of Gathr.</p>
                <p style="margin: 0;">&copy; {{ date('Y') }} Gathr. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
