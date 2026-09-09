{{-- Rendered inside the viewer's iframe, so it carries its own head: a Word
     file has no browser viewer, and a blank frame with no explanation reads
     as a broken page rather than as a file we cannot draw. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $name }}</title>
    <style>
        body {
            margin: 0;
            padding: 26px 30px;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #272827;
            background: #fff;
            font-size: .95rem;
            line-height: 1.7;
        }
        .note {
            margin: 0 0 20px;
            padding: 10px 14px;
            background: #f7f6f5;
            border-left: 3px solid #c73a41;
            border-radius: 8px;
            color: #5f605f;
            font-size: .85rem;
        }
        pre {
            margin: 0;
            font: inherit;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
        .empty { color: #5f605f; }
    </style>
</head>
<body>
    @if ($text)
        <p class="note">
            Text read from <strong>{{ $name }}</strong>. Layout, images and formatting are not shown —
            download the file if you need it exactly as it was written.
        </p>
        <pre>{{ $text }}</pre>
    @else
        <p class="note">No preview for <strong>{{ $name }}</strong></p>
        <p class="empty">
            This file holds no text we can read — an older Word format, or a CV that was scanned as a picture.
            Download it to open it in full.
        </p>
    @endif
</body>
</html>
