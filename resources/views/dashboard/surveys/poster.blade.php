<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $survey->name }} — Scan to check in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap">
    <style>
        :root { --brand: #c73a41; --ink: #272827; --ink-soft: #5f605f; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Montserrat", -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--ink); background: #f7f6f5;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px;
        }
        .sheet {
            background: #fff; width: 100%; max-width: 620px; text-align: center;
            padding: 56px 48px; border-radius: 18px; border: 1px solid #e2e8f0;
        }
        .logo { height: 46px; margin-bottom: 28px; }
        .eyebrow {
            font-size: .72rem; font-weight: 700; letter-spacing: .16em;
            text-transform: uppercase; color: var(--brand); margin-bottom: 10px;
        }
        h1 { font-size: 2.3rem; line-height: 1.1; letter-spacing: -.02em; margin-bottom: 10px; }
        .lead { color: var(--ink-soft); font-size: 1rem; margin-bottom: 32px; }
        .qr {
            display: inline-flex; align-items: center; justify-content: center;
            width: 356px; height: 356px; padding: 18px;
            border: 2px solid var(--ink); border-radius: 16px;
        }
        .qr canvas { display: block; }
        /* Offline / blocked CDN: the frame keeps its place on the sheet. */
        .qr.is-missing { border-color: var(--brand); background: #faeaeb; }
        .qr-fallback { display: flex; flex-direction: column; gap: 8px; padding: 22px; }
        .qr-fallback strong { font-size: 1.05rem; color: var(--brand); }
        .qr-fallback span { font-size: .85rem; color: var(--ink-soft); line-height: 1.5; }
        .url {
            margin-top: 26px; font-size: 1rem; font-weight: 700; color: var(--ink);
            word-break: break-all;
        }
        .hint { margin-top: 8px; font-size: .82rem; color: var(--ink-soft); }
        .bar { margin-top: 34px; display: flex; gap: 10px; justify-content: center; }
        .bar button, .bar a {
            font-family: inherit; font-size: .88rem; font-weight: 600; cursor: pointer;
            padding: 11px 20px; border-radius: 10px; text-decoration: none;
            border: 1.5px solid #e2e8f0; background: #fff; color: var(--ink);
        }
        .bar .primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: none; max-width: none; padding: 40px; }
            .bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <img class="logo" src="{{ asset('img/logo.png') }}" alt="Applyd Academy">

        <div class="eyebrow">{{ $survey->eyebrow ? $survey->eyebrow.' · ' : '' }}Pulse Check</div>
        <h1>{{ $survey->name }}</h1>
        <p class="lead">{{ $survey->blurb }} Point your camera at the code.</p>

        <div class="qr" id="qrBox">
            <canvas id="qr" width="320" height="320"></canvas>
            <div class="qr-fallback" hidden>
                <strong>QR unavailable</strong>
                <span>This machine couldn't reach the QR generator. The link below still works — or reload once you're online.</span>
            </div>
        </div>

        <div class="url">{{ $url }}</div>
        <div class="hint">Takes under a minute · no sign-up needed</div>

        <div class="bar">
            <button type="button" class="primary" onclick="window.print()">Print</button>
            <button type="button" id="download">Download PNG</button>
            <a href="{{ route('dashboard.surveys', ['survey' => $survey->slug]) }}">Back to results</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
    <script>
        var canvas = document.getElementById('qr');
        var box = document.getElementById('qrBox');
        var download = document.getElementById('download');
        var drawn = false;

        try {
            if (window.QRious) {
                new QRious({
                    element: canvas,
                    value: @json($url),
                    size: 320,
                    level: 'M',        // survives a bit of print smudge
                    background: '#ffffff',
                    foreground: '#272827',
                });
                drawn = true;
            }
        } catch (e) { /* fall through to the placeholder */ }

        if (!drawn) {
            box.classList.add('is-missing');
            canvas.hidden = true;
            box.querySelector('.qr-fallback').hidden = false;
            download.disabled = true;
        }

        download.addEventListener('click', function () {
            if (!drawn) return;
            var link = document.createElement('a');
            link.download = 'pulse-check-{{ $survey->slug }}-qr.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    </script>
</body>
</html>
