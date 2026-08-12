<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Update</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #004D80 0%, #007AC1 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .header .icon { font-size: 48px; margin-bottom: 10px; }
        .header h1 { margin: 0 0 8px 0; font-size: 26px; font-weight: bold; }
        .header p  { margin: 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 16px; color: #333; margin-bottom: 20px; }
        .info-box {
            background: #EAF6FF;
            border: 1px solid #B3D9F5;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 28px;
        }
        .info-box h2 { margin: 0 0 16px 0; color: #004D80; font-size: 18px; }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #C9E8F8;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #555; font-size: 14px; }
        .detail-value { color: #1a1a1a; font-weight: 700; font-size: 14px; }
        .amount-highlight { color: #007AC1; font-size: 20px; font-weight: 800; }
        .notice-box {
            background: #FFF9E6;
            border: 1px solid #FFE082;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }
        .notice-box p { margin: 0; font-size: 13px; color: #7B5800; line-height: 1.6; }
        .cta-button {
            display: inline-block;
            background: #009EE3;
            color: white !important;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 15px;
        }
        .footer {
            background: #F8FAFB;
            padding: 24px 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #E5E7EB;
        }
        .footer p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ url('/logo.png') }}" alt="Gathr" style="height: 44px; width: auto; margin-bottom: 16px;">
            <div class="icon">💸</div>
            <h1>Funds Withdrawn</h1>
            <p>An update on your collection contribution</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Hi {{ $contributorName }},</p>
            <p style="font-size:15px; color:#555; margin-bottom:24px; line-height:1.6;">
                We're letting you know that <strong>{{ $organizerName }}</strong>, the organizer of
                <strong>{{ $collectionName }}</strong>, has made a withdrawal from the funds you contributed to.
            </p>

            <div class="info-box">
                <h2>{{ $collectionName }}</h2>

                <div class="detail-row">
                    <span class="detail-label">Organizer</span>
                    <span class="detail-value">{{ $organizerName }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Amount withdrawn</span>
                    <span class="detail-value amount-highlight">₦{{ number_format($withdrawalAmount) }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Total raised</span>
                    <span class="detail-value">₦{{ number_format($totalRaised) }}</span>
                </div>
            </div>

            <div class="notice-box">
                <p>
                    💡 Your payment was confirmed and received. This email is simply to keep you informed of
                    how the funds are being used. If you have any questions or concerns, please contact the organizer directly.
                </p>
            </div>

            <div style="text-align: center;">
                <a href="{{ url('/c/' . $collectionSlug) }}" class="cta-button">
                    View Collection →
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} Gathr. All rights reserved.</p>
            <p>You received this because you contributed to this collection.</p>
            <p>Payment secured by ZainPay.</p>
        </div>
    </div>
</body>
</html>
