<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FIL Widget (iframe)</title>
    <style>body { margin: 1rem; }</style>
</head>
<body>
    <div
        data-fil-widget="lead-form"
        data-site-key="{{ $siteKey }}"
        data-api-base="{{ $apiBase }}"
    ></div>
    <script src="{{ asset('widget/form.js') }}"></script>
</body>
</html>
