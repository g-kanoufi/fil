import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card, CardHeader } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { LoadingState } from '@/components/ui/LoadingState';
import { fetchWidgetForms, type WidgetFormDef } from '@/lib/api/widgetForms';
import { useAuth } from '@/providers/AuthProvider';

export function WidgetDemoPage() {
  const { can } = useAuth();
  const canManage = can('fields.manage');

  const [forms, setForms] = useState<WidgetFormDef[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [copyStatus, setCopyStatus] = useState<string | null>(null);
  const [themePrimary, setThemePrimary] = useState('#7c3aed');
  const [themeError, setThemeError] = useState('#dc2626');

  const iframeRef = useRef<HTMLIFrameElement>(null);

  const previewForm = useMemo(
    () => forms.find((form) => form.status === 'active' && form.site_key) ?? forms[0] ?? null,
    [forms],
  );

  const apiBase = window.location.origin;

  const embedSnippet = useMemo(() => {
    if (!previewForm?.site_key) {
      return '';
    }

    return `<div
  data-fil-widget="lead-form"
  data-site-key="${previewForm.site_key}"
  data-api-base="${apiBase}"
  data-fil-primary="#0f766e"
  data-fil-error="#b91c1c"
></div>
<script src="${apiBase}/widget/form.js" defer></script>`;
  }, [apiBase, previewForm?.site_key]);

  useEffect(() => {
    if (!canManage) {
      setLoading(false);
      return;
    }

    void fetchWidgetForms()
      .then(setForms)
      .catch((loadError: unknown) => {
        setError(loadError instanceof Error ? loadError.message : 'Failed to load widget forms');
      })
      .finally(() => setLoading(false));
  }, [canManage]);

  const copySnippet = useCallback(async () => {
    if (!embedSnippet) {
      return;
    }

    try {
      await navigator.clipboard.writeText(embedSnippet);
      setCopyStatus('Snippet copied.');
      window.setTimeout(() => setCopyStatus(null), 2500);
    } catch {
      setCopyStatus('Copy failed — select the snippet manually.');
    }
  }, [embedSnippet]);

  const applyIframeTheme = useCallback(() => {
    iframeRef.current?.contentWindow?.postMessage(
      {
        type: 'fil-widget-theme',
        theme: { primary: themePrimary, error: themeError, primaryHover: themePrimary },
      },
      window.location.origin,
    );
  }, [themeError, themePrimary]);

  if (!canManage) {
    return (
      <>
        <PageHeader title="Widget demo" description="Admin access required." />
        <Alert variant="error">You do not have permission to preview the embed widget.</Alert>
      </>
    );
  }

  return (
    <>
      <PageHeader
        title="Widget demo"
        description="Preview the public lead form and learn how to embed it on a client marketing site."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <Link to="/settings/widget" className="text-link hover:underline">
          ← Widget form builder
        </Link>
        <Link to="/settings" className="text-link hover:underline">
          Settings
        </Link>
      </div>

      {error ? (
        <Alert variant="error" className="mb-4">
          {error}
        </Alert>
      ) : null}

      <Alert variant="info" className="mb-6">
        This preview is for <strong>staff only</strong>. Prospects should never visit this page — embed
        the script on the client&apos;s marketing domain instead.
      </Alert>

      {loading ? <LoadingState label="Loading widget forms…" /> : null}

      {!loading ? (
        <>
          <Card className="mb-6">
            <CardHeader
              title="How to embed on a client site"
              description="Each widget form has its own publishable site key. FIL validates the key and the browser origin on every intake request."
            />
            <div className="space-y-4 text-sm text-muted">
              <ol className="list-decimal space-y-2 pl-5">
                <li>
                  In <Link to="/settings/widget" className="text-link hover:underline">Settings → Widget form</Link>,
                  create or select a form and arrange the fields you want on the public form.
                </li>
                <li>
                  Copy the embed snippet (below or from the builder) into the client page — WordPress
                  Custom HTML block, Webflow embed, Squarespace code injection, etc.
                </li>
                <li>
                  Set <code className="text-foreground">data-site-key</code> to that form&apos;s key and{' '}
                  <code className="text-foreground">data-api-base</code> to this FIL instance URL (
                  <code className="text-foreground">{apiBase}</code>) when the page is not served from
                  the same host.
                </li>
                <li>
                  In production, add every client marketing origin to{' '}
                  <code className="text-foreground">FIL_EMBED_ALLOWED_ORIGINS</code> (comma-separated,
                  scheme + host, e.g. <code className="text-foreground">https://www.client.com</code>).
                  Requests from origins not on the list are rejected. The FIL app origin is always allowed
                  so this staff preview works without listing it.
                </li>
                <li>
                  Configure <code className="text-foreground">FIL_RECAPTCHA_*</code> in production for bot
                  protection on intake.
                </li>
                <li>
                  Publish the client page, submit a test lead, and confirm it appears under{' '}
                  <Link to="/reports/leads" className="text-link hover:underline">Leads</Link>.
                </li>
                <li>
                  If a site key is exposed, use <strong>Rotate site key</strong> in the builder and update
                  the client snippet.
                </li>
              </ol>

              <div className="rounded-lg border border-border bg-surface-muted p-4">
                <p className="mb-2 font-medium text-foreground">Optional theme tokens</p>
                <p>
                  Add <code className="text-foreground">data-fil-primary</code>,{' '}
                  <code className="text-foreground">data-fil-primary-hover</code>, and{' '}
                  <code className="text-foreground">data-fil-error</code> on the mount node to match the
                  client brand. For iframe embeds, the parent page can send theme updates with{' '}
                  <code className="text-foreground">postMessage</code> (see iframe preview below).
                </p>
              </div>

              {previewForm?.site_key ? (
                <div className="space-y-2">
                  <p>
                    <span className="font-medium text-foreground">Preview site key: </span>
                    <code className="rounded bg-surface-muted px-2 py-1 text-xs text-foreground">
                      {previewForm.site_key}
                    </code>
                    <span className="ml-2 text-muted">({previewForm.name})</span>
                  </p>
                  <div className="flex flex-wrap items-center gap-2">
                    <Button size="sm" variant="secondary" onClick={() => void copySnippet()} disabled={!embedSnippet}>
                      Copy embed snippet
                    </Button>
                    {copyStatus ? <span className="text-xs text-muted">{copyStatus}</span> : null}
                  </div>
                  <pre className="overflow-x-auto rounded-lg border border-border bg-surface-muted p-3 text-xs text-foreground">
                    {embedSnippet}
                  </pre>
                </div>
              ) : (
                <p>
                  No active widget form yet.{' '}
                  <Link to="/settings/widget" className="text-link hover:underline">
                    Create one in the builder
                  </Link>{' '}
                  to generate a site key and snippet.
                </p>
              )}
            </div>
          </Card>

          <div className="grid gap-6 lg:grid-cols-2">
            <Card>
              <CardHeader
                title="Live preview — inline embed"
                description="Same pattern as a client marketing page: mount node + form.js on the page."
              />
              {previewForm?.site_key ? (
                <iframe
                  title="FIL widget inline preview"
                  src="/embed-demo/inline"
                  className="h-[520px] w-full max-w-lg rounded-lg border border-border bg-surface"
                />
              ) : (
                <p className="text-sm text-muted">Create a widget form to load the preview.</p>
              )}
            </Card>

            <Card>
              <CardHeader
                title="Live preview — iframe embed"
                description="Host a minimal page on the client site, then iframe it. Theme can be updated from the parent via postMessage."
              />
              {previewForm?.site_key ? (
                <>
                  <div className="mb-3 flex flex-wrap items-end gap-3 text-sm">
                    <label className="grid gap-1">
                      <span className="font-medium text-foreground">Primary</span>
                      <input
                        type="color"
                        value={themePrimary}
                        onChange={(event) => setThemePrimary(event.target.value)}
                        className="h-9 w-12 cursor-pointer rounded border border-border"
                      />
                    </label>
                    <label className="grid gap-1">
                      <span className="font-medium text-foreground">Error</span>
                      <input
                        type="color"
                        value={themeError}
                        onChange={(event) => setThemeError(event.target.value)}
                        className="h-9 w-12 cursor-pointer rounded border border-border"
                      />
                    </label>
                    <Button size="sm" variant="secondary" onClick={applyIframeTheme}>
                      Apply to iframe
                    </Button>
                  </div>
                  <iframe
                    ref={iframeRef}
                    title="FIL widget iframe preview"
                    src="/embed-demo/frame"
                    className="h-[520px] w-full max-w-lg rounded-lg border border-border bg-surface"
                  />
                </>
              ) : (
                <p className="text-sm text-muted">Create a widget form to load the preview.</p>
              )}
            </Card>
          </div>
        </>
      ) : null}
    </>
  );
}
