import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '@/components/ui/PageHeader';
import { Card, CardHeader } from '@/components/ui/Card';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { LoadingState } from '@/components/ui/LoadingState';
import {
  fetchMailSettings,
  sendMailTest,
  verifyMailgunDomain,
  type MailSettingsStatus,
} from '@/lib/api/mailSettings';
import { useAuth } from '@/providers/AuthProvider';
import { badge } from '@/lib/ui/tokens';

function statusBadge(configured: boolean, usingMailgun: boolean): { label: string; className: string } {
  if (configured) {
    return { label: 'Ready for production', className: badge.success };
  }

  if (usingMailgun) {
    return { label: 'Mailgun configured — verify domain', className: badge.warning };
  }

  return { label: 'Development mailer', className: badge.default };
}

export function MailSettingsPage() {
  const { user } = useAuth();
  const canManage = user?.permissions.includes('settings.manage') ?? false;
  const [status, setStatus] = useState<MailSettingsStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [testRecipient, setTestRecipient] = useState(user?.email ?? '');
  const [busyAction, setBusyAction] = useState<'verify' | 'test' | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setStatus(await fetchMailSettings());
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load mail settings');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (canManage) {
      void load();
    }
  }, [canManage, load]);

  useEffect(() => {
    if (user?.email) {
      setTestRecipient(user.email);
    }
  }, [user?.email]);

  async function handleVerify() {
    setBusyAction('verify');
    setError(null);
    setSuccess(null);

    try {
      const result = await verifyMailgunDomain();
      setStatus(result.status);
      setSuccess(
        result.mailgun.verified
          ? `Mailgun domain verified (${result.mailgun.domain_state ?? 'active'}).`
          : result.mailgun.error ?? 'Domain verification failed.',
      );
    } catch (verifyError: unknown) {
      setError(verifyError instanceof Error ? verifyError.message : 'Verification failed');
    } finally {
      setBusyAction(null);
    }
  }

  async function handleSendTest() {
    setBusyAction('test');
    setError(null);
    setSuccess(null);

    try {
      const result = await sendMailTest(testRecipient.trim() || undefined);
      const deliveredTo = result.recipient;
      const intended = result.intended_recipient ?? deliveredTo;

      setSuccess(
        intended !== deliveredTo
          ? `Test email routed to ${deliveredTo} (intended: ${intended}). Real recipients are blocked outside production.`
          : `Test email sent to ${deliveredTo}.`,
      );
    } catch (testError: unknown) {
      setError(testError instanceof Error ? testError.message : 'Test email failed');
    } finally {
      setBusyAction(null);
    }
  }

  if (!canManage) {
    return (
      <>
        <PageHeader title="Mail delivery" description="Admin access required." />
        <Alert variant="error">You do not have permission to manage mail settings.</Alert>
      </>
    );
  }

  const badge = status ? statusBadge(status.configured, status.using_mailgun) : null;

  return (
    <>
      <PageHeader
        title="Mail delivery"
        description="Verify Mailgun configuration before enabling production notifications and drip emails."
      />

      <div className="mb-4">
        <Link to="/settings" className="text-sm text-link hover:underline">
          ← Settings
        </Link>
      </div>

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}
      {success ? <Alert variant="success" className="mb-4">{success}</Alert> : null}
      {!loading && status?.outbound.guarded ? (
        <Alert variant="info" className="mb-4">
          Outbound mail is guarded in this environment. Messages never go to real customer addresses
          {status.outbound.sink_addresses.length > 0
            ? ` — they are routed to ${status.outbound.sink_addresses.join(', ')}.`
            : ' — they go to a safe placeholder address and/or the log mailer.'}
        </Alert>
      ) : null}

      {loading ? <LoadingState label="Loading mail settings…" /> : null}

      {!loading && status ? (
        <div className="grid gap-4 lg:grid-cols-2">
          <Card>
            <CardHeader title="Current configuration" />
            <div className="space-y-3 text-sm">
              {badge ? (
                <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${badge.className}`}>
                  {badge.label}
                </span>
              ) : null}
              <dl className="grid gap-2">
                <div className="flex justify-between gap-4">
                  <dt className="text-muted">Mailer</dt>
                  <dd className="font-medium">{status.mailer}</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-muted">From</dt>
                  <dd className="font-medium">{status.from_name} &lt;{status.from_address}&gt;</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-muted">Effective mailer</dt>
                  <dd className="font-medium">{status.outbound.effective_mailer}</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-muted">Real recipients</dt>
                  <dd className="font-medium">
                    {status.outbound.allows_real_recipients ? 'Allowed (production)' : 'Blocked'}
                  </dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-muted">Mailgun domain</dt>
                  <dd className="font-medium">{status.mailgun.domain ?? '—'}</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-muted">Domain state</dt>
                  <dd className="font-medium">{status.mailgun.domain_state ?? '—'}</dd>
                </div>
                <div className="flex justify-between gap-4">
                  <dt className="text-muted">Webhook signing</dt>
                  <dd className="font-medium">
                    {status.mailgun.webhook_signing_configured ? 'Configured' : 'Not set'}
                  </dd>
                </div>
              </dl>
              {status.mailgun.error ? (
                <p className="rounded-lg bg-amber-50 px-3 py-2 text-amber-900">{status.mailgun.error}</p>
              ) : null}
              <div className="flex flex-wrap gap-2 pt-2">
                <Button
                  size="sm"
                  variant="secondary"
                  onClick={() => void handleVerify()}
                  disabled={busyAction !== null}
                >
                  {busyAction === 'verify' ? 'Verifying…' : 'Verify Mailgun domain'}
                </Button>
                <Button size="sm" variant="secondary" onClick={() => void load()} disabled={loading}>
                  Refresh
                </Button>
              </div>
            </div>
          </Card>

          <Card>
            <CardHeader
              title="Send test email"
              description="Uses the active mailer (log, SMTP, or Mailgun)."
            />
            <div className="space-y-3">
              <label className="flex flex-col gap-1 text-sm">
                <span className="font-medium text-foreground">Recipient</span>
                <input
                  type="email"
                  value={testRecipient}
                  onChange={(event) => setTestRecipient(event.target.value)}
                  className="rounded-lg border border-border bg-surface px-3 py-2"
                  placeholder="admin@fil.test"
                />
              </label>
              <Button onClick={() => void handleSendTest()} disabled={busyAction !== null}>
                {busyAction === 'test' ? 'Sending…' : 'Send test email'}
              </Button>
            </div>
          </Card>

          <Card className="lg:col-span-2">
            <CardHeader title="Production checklist" />
            <ol className="list-decimal space-y-2 pl-5 text-sm text-muted">
              <li>Set <code className="text-xs">MAIL_MAILER=mailgun</code>, <code className="text-xs">MAILGUN_DOMAIN</code>, and <code className="text-xs">MAILGUN_SECRET</code> on Forge.</li>
              <li>Set <code className="text-xs">MAIL_FROM_ADDRESS</code> to a sender on the verified domain.</li>
              <li>Configure Mailgun webhook URL: <code className="text-xs">/api/webhooks/mailgun</code> with signing key in <code className="text-xs">MAILGUN_WEBHOOK_SIGNING_KEY</code>.</li>
              <li>Run <code className="text-xs">php artisan mail:verify --send=you@client.com</code> after deploy.</li>
            </ol>
          </Card>
        </div>
      ) : null}
    </>
  );
}
