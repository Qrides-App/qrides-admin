<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isApproved ? 'Open In Qrides' : 'Driver Not Available Yet' }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f7fb;
            --card: #ffffff;
            --text: #14213d;
            --muted: #667085;
            --primary: #2f80ed;
            --accent: #f1b500;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(47, 128, 237, 0.14), transparent 30%),
                radial-gradient(circle at bottom right, rgba(241, 181, 0, 0.18), transparent 28%),
                var(--bg);
            color: var(--text);
        }
        .card {
            width: min(100%, 460px);
            background: var(--card);
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 24px 64px rgba(20, 33, 61, 0.12);
        }
        .badge {
            display: inline-flex;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(47, 128, 237, 0.12);
            color: var(--primary);
            font-weight: 700;
            font-size: 13px;
        }
        h1 {
            margin: 16px 0 8px;
            font-size: 30px;
            line-height: 1.05;
        }
        p {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.6;
        }
        .actions {
            display: grid;
            gap: 12px;
            margin-top: 22px;
        }
        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 52px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            transition: transform .15s ease, opacity .15s ease;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #1e63c6);
            color: #fff;
        }
        .btn-secondary {
            border: 1px solid rgba(20, 33, 61, 0.12);
            color: var(--text);
            background: #fff;
        }
        .note {
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(241, 181, 0, 0.12);
            color: #7a5a00;
            font-weight: 600;
            line-height: 1.5;
        }
        code {
            display: block;
            width: 100%;
            overflow-wrap: anywhere;
            background: rgba(20, 33, 61, 0.05);
            border-radius: 14px;
            padding: 12px 14px;
            margin-top: 12px;
            font-size: 13px;
            color: var(--text);
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="badge">Qrides QR Hire</span>
        <h1>{{ $isApproved ? 'Open in the Qrides app' : 'Driver not available yet' }}</h1>
        <p>
            @if ($isApproved)
                Use the button below to continue the hire flow for driver ID {{ $driverId }}.
            @else
                Driver ID {{ $driverId }} is not approved yet. QR hire stays disabled until admin approval is complete.
            @endif
        </p>

        <div class="actions">
            @if ($isApproved)
                <a class="btn btn-primary" id="open-app" href="{{ $appLink }}">Open In Qrides</a>
            @else
                <div class="note">This driver has not been approved yet, so the hire link is intentionally disabled.</div>
            @endif
            <a class="btn btn-secondary" href="https://play.google.com/store">Get Android app</a>
        </div>

        <code>{{ $isApproved ? $appLink : 'QR hire unavailable until approval' }}</code>
    </main>

    @if ($isApproved)
        <script>
            window.setTimeout(function () {
                window.location.href = @json($appLink);
            }, 250);
        </script>
    @endif
</body>
</html>
