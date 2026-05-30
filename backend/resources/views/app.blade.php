<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>FIL</title>
    @php
        $manifestPath = public_path('fil-assets/.vite/manifest.json');
        $entry = null;

        if (is_readable($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            $entry = $manifest['src/main.tsx'] ?? null;
        }
    @endphp
    @if ($entry)
        @if (! empty($entry['css']))
            @foreach ($entry['css'] as $stylesheet)
                <link rel="stylesheet" href="{{ asset('fil-assets/'.$stylesheet) }}">
            @endforeach
        @endif
        <script type="module" src="{{ asset('fil-assets/'.$entry['file']) }}"></script>
    @endif
</head>
<body>
    <div id="root"></div>
</body>
</html>
