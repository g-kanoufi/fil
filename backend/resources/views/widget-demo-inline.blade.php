<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FIL Widget (inline preview)</title>
    <style>body { margin: 1rem; font-family: system-ui, sans-serif; }</style>
</head>
<body>
    <div
        data-fil-widget="lead-form"
        data-site-key="{{ $siteKey }}"
        data-api-base="{{ $apiBase }}"
        data-fil-primary="#0f766e"
        data-fil-primary-hover="#115e59"
        data-fil-error="#b91c1c"
    ></div>
    <script src="{{ asset('widget/form.js') }}"></script>
</body>
</html>
