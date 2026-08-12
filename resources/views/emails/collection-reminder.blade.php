<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Reminder</title>
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
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .reminder-box {
            background: #FFF3E0;
            border: 2px solid #FFE0B2;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }
        .reminder-box h2 {
            margin: 0 0 16px 0;
            color: #E65100;
            font-size: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #FFE0B2;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
            font-size: 14px;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background: #009EE3;
            color: white;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background: #007AC1;
        }
        .footer {
            background: #F8FAFB;
            padding: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #E5E7EB;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ url('/logo.png') }}" alt="Gathr" style="height: 44px; width: auto; margin-bottom: 16px;">
            <div class="icon">🔔</div>
            <h1>Payment Reminder</h1>
            <p>You asked us to remind you about this collection</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="reminder-box">
                <h2>{{ $collectionName }}</h2>
                
                <div class="detail-row">
                    <span class="detail-label">Organized by</span>
                    <span class="detail-value">{{ $organizerName }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount per person</span>
                    <span class="detail-value">₦{{ number_format($amount) }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Deadline</span>
                    <span class="detail-value">{{ $deadline }}</span>
                </div>

                @if($customMessage)
                <div class="detail-row">
                    <span class="detail-label">Note</span>
                    <span class="detail-value">{{ $customMessage }}</span>
                </div>
                @endif
            </div>

            <p style="text-align: center; color: #666; font-size: 14px; margin: 30px 0;">
                Don't miss out! Make sure to pay before the deadline to secure your spot.
            </p>

            <div style="text-align: center;">
                <a href="{{ url('/c/' . $slug) }}" class="cta-button">
                    Pay Now →
                </a>
            </div>

            <p style="text-align: center; color: #999; font-size: 12px; margin-top: 30px;">
                This reminder was requested by you. If you have any questions, please contact the collection organizer.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} Gathr. All rights reserved.</p>
            <p>This is an automated reminder email.</p>
        </div>
    </div>
</body>
</html>
