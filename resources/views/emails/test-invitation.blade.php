<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background: #2563eb;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 28px;">Typing Test Invitation</h1>
    </div>

    <div class="content">
        <p style="font-size: 18px;">Hi {{ $candidate->name }},</p>

        <p>
            Thank you for your interest in the <strong>{{ $candidate->position_applied }}</strong> position!
            As part of our recruitment process, we'd like to invite you to complete a typing test.
        </p>

        <div class="info-box">
            <strong>What to expect:</strong>
            <ul style="margin: 10px 0;">
                <li>The test takes approximately 3-5 minutes</li>
                <li>You'll type a provided text sample</li>
                <li>We'll measure your typing speed (WPM) and accuracy</li>
                <li>The link expires on <strong>{{ $expiresAt->format('M d, Y g:i A') }}</strong></li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ $testUrl }}" class="button">
                Start Typing Test
            </a>
        </div>

        <p style="color: #6b7280; font-size: 14px;">
            If the button doesn't work, copy and paste this link into your browser:<br>
            <a href="{{ $testUrl }}" style="color: #3b82f6;">{{ $testUrl }}</a>
        </p>

        <p>
            If you have any questions or encounter any issues, please don't hesitate to reach out.
        </p>

        <p>
            Best regards,<br>
            <strong>{{ config('app.name') }} Team</strong>
        </p>
    </div>

    <div class="footer">
        <p>This invitation was sent to {{ $candidate->email }}</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
