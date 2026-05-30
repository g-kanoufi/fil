<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FIL Widget Demo</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; max-width: 52rem; line-height: 1.5; }
        code { background: #f4f4f4; padding: 0.15rem 0.35rem; border-radius: 4px; font-size: 0.9em; }
        section { margin: 2rem 0; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
        .theme-controls { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end; margin-bottom: 1rem; }
        .theme-controls label { display: grid; gap: 0.25rem; font-size: 0.875rem; }
        .theme-controls input[type="color"] { width: 3rem; height: 2rem; padding: 0; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; }
        .theme-controls button { padding: 0.45rem 0.9rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; }
        iframe { width: 100%; max-width: 32rem; height: 520px; border: 1px solid #d1d5db; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>FIL embed widget demo</h1>
    <p>
        Loads <code>/widget/form.js</code> with site key <code>pk_dev</code>.
        Styles use CSS variables on the mount element and can be updated at runtime (including inside an iframe via <code>postMessage</code>).
    </p>

    <section>
        <h2>Inline embed (data attributes)</h2>
        <p>Override tokens with <code>data-fil-*</code> on the mount node, e.g. <code>data-fil-primary</code>, <code>data-fil-error</code>.</p>
        <div
            data-fil-widget="lead-form"
            data-site-key="pk_dev"
            data-api-base="{{ url('/') }}"
            data-fil-primary="#0f766e"
            data-fil-primary-hover="#115e59"
            data-fil-error="#b91c1c"
        ></div>
    </section>

    <section>
        <h2>Iframe embed (postMessage theming)</h2>
        <p>Parent pages can push theme updates without reloading:</p>
        <div class="theme-controls">
            <label>Primary <input type="color" id="theme-primary" value="#7c3aed"></label>
            <label>Error <input type="color" id="theme-error" value="#dc2626"></label>
            <button type="button" id="apply-iframe-theme">Apply to iframe</button>
        </div>
        <iframe id="widget-frame" src="{{ url('/embed-demo/frame') }}" title="FIL widget iframe"></iframe>
    </section>

    <script src="{{ asset('widget/form.js') }}"></script>
    <script>
        document.getElementById('apply-iframe-theme')?.addEventListener('click', () => {
            const frame = document.getElementById('widget-frame');
            const primary = document.getElementById('theme-primary')?.value;
            const error = document.getElementById('theme-error')?.value;
            frame?.contentWindow?.postMessage({
                type: 'fil-widget-theme',
                theme: { primary, error, primaryHover: primary },
            }, '*');
        });
    </script>
</body>
</html>
