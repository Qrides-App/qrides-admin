<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailData['general_name'] ?? config('app.name') }}</title>
    <style>
        body {
            margin: 0;
            padding: 24px 0;
            background: #f3f7fa;
            color: #334155;
            font-family: Arial, Helvetica, sans-serif;
        }

        .mail-wrapper {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        .mail-header {
            padding: 24px 28px;
            background: linear-gradient(135deg, #0f766e, #14b8a6);
            color: #ffffff;
        }

        .mail-header h1 {
            margin: 0 0 6px;
            font-size: 24px;
            font-weight: 700;
        }

        .mail-header p {
            margin: 0;
            color: rgba(255, 255, 255, 0.86);
            font-size: 14px;
        }

        .mail-body {
            padding: 28px;
            line-height: 1.75;
            font-size: 15px;
        }

        .mail-body h1,
        .mail-body h2,
        .mail-body h3,
        .mail-body h4 {
            color: #0f172a;
        }

        .mail-body a {
            color: #0f766e;
            text-decoration: none;
            font-weight: 600;
        }

        .mail-footer {
            padding: 18px 28px 24px;
            border-top: 1px solid #e5eef4;
            background: #f8fafc;
            color: #64748b;
            font-size: 13px;
        }

        .mail-footer p {
            margin: 0 0 8px;
        }
    </style>
</head>

<body>
    <div class="mail-wrapper">
        <div class="mail-header">
            <h1>{{ $emailData['general_name'] ?? config('app.name') }}</h1>
            <p>Transactional notification</p>
        </div>

        <div class="mail-body">
            {!! $emailData['data'] !!}
        </div>

        <div class="mail-footer">
            @if (!empty($emailData['general_email']))
                <p>Contact us: <a href="mailto:{{ $emailData['general_email'] }}">{{ $emailData['general_email'] }}</a></p>
            @endif
            @if (!empty($emailData['general_phone']))
                <p>Phone: {{ $emailData['general_default_phone_country'] ?? '' }}{{ $emailData['general_phone'] }}</p>
            @endif
            <p>&copy; {{ date('Y') }} {{ $emailData['general_name'] ?? config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
