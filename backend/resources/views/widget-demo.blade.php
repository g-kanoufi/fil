<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FIL Widget Demo (staff)</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; max-width: 56rem; line-height: 1.5; color: #111827; }
        code, pre { background: #f4f4f5; border-radius: 6px; font-size: 0.875rem; }
        code { padding: 0.15rem 0.35rem; }
        pre { padding: 1rem; overflow-x: auto; border: 1px solid #e5e7eb; }
        section { margin: 2rem 0; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
        .note { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.9rem; }
        .theme-controls { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end; margin-bottom: 1rem; }
        .theme-controls label { display: grid; gap: 0.25rem; font-size: 0.875rem; }
        .theme-controls input[type="color"] { width: 3rem; height: 2rem; padding: 0; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; }
        .theme-controls button { padding: 0.45rem 0.9rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; }
        iframe { width: 100%; max-width: 32rem; height: 520px; border: 1px solid #d1d5db; border-radius: 8px; }
        ol { padding-left: 1.25rem; }
        li + li { margin-top: 0.35rem; }
    </style>
</head>
<body>
    <h1>FIL embed widget — staff preview</h1>
    <p class="note">
        This page is <strong>staff-only</strong>. Client marketing sites should embed the script below on
        <em>their</em> domain — never link prospects to this URL.
    </p>

    <section>
        <h2>Client site setup</h2>
        <p>
            Each widget form has a unique <strong>site key</strong> (publishable, like a Stripe publishable key).
            FIL validates the key on every intake request; in production also configure
            <code>FIL_RECAPTCHA_*</code> for bot protection.
        </p>
        <ol>
            <li>In FIL → <strong>Settings → Widget form</strong>, create or select a form and copy its site key.</li>
            <li>Add the script + mount node to the client page (WordPress HTML block, Webflow embed, etc.).</li>
            <li>Set <code>data-site-key</code> to the form’s key and <code>data-api-base</code> to this FIL instance URL.</li>
            <li>Publish the client page and submit a test lead; confirm it appears under Leads.</li>
            <li>If the key is compromised, use <strong>Rotate site key</strong> in Settings — update the client embed snippet.</li>
        </ol>

        <p><strong>Active site key for this preview:</strong> <code>{{ $siteKey }}</code></p>

        <h3>Inline embed (recommended)</h3>
        <pre>&lt;div
  data-fil-widget="lead-form"
  data-site-key="{{ $siteKey }}"
  data-api-base="{{ $apiBase }}"
  data-fil-primary="#0f766e"
  data-fil-error="#b91c1c"
&gt;&lt;/div&gt;
&lt;script src="{{ $apiBase }}/widget/form.js" defer&gt;&lt;/script&gt;</pre>

        <h3>Iframe embed</h3>
        <p>Host a minimal page on the client site with the same mount node + script, then iframe that URL. Theme updates can use <code>postMessage</code> (see preview below).</p>
    </section>

    <section>
        <h2>Live preview — inline</h2>
        <p>Override tokens with <code>data-fil-*</code> on the mount node.</p>
        <div
            data-fil-widget="lead-form"
            data-site-key="{{ $siteKey }}"
            data-api-base="{{ $apiBase }}"
            data-fil-primary="#0f766e"
            data-fil-primary-hover="#115e59"
            data-fil-error="#b91c1c"
        ></div>
    </section>

    <section>
        <h2>Live preview — iframe + postMessage theming</h2>
        <div class="theme-controls">
            <label>Primary <input type="color" id="theme-primary" value="#7c3aed"></label>
            <label>Error <input type="color" id="theme-error" value="#dc2626"></label>
            <button type="button" id="apply-iframe-theme">Apply to iframe</button>
        </div>
        <iframe id="widget-frame" src="{{ route('widget.demo.frame') }}" title="FIL widget iframe"></iframe>
    </section>

    <p><a href="{{ url('/app/settings/widget') }}">← Back to widget form builder</a></p>

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
