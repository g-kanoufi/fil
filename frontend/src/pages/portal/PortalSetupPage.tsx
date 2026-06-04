import { FormEvent, useState } from 'react';
import { Navigate, useSearchParams } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { ApiError } from '@/lib/api/client';
import { loginInner, loginShell } from '@/lib/ui/tokens';
import { usePortalAuth } from '@/providers/PortalAuthProvider';

export function PortalSetupPage() {
  const { status, setupPassword } = usePortalAuth();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (status === 'authenticated') {
    return <Navigate to="/application" replace />;
  }

  if (!token) {
    return (
      <Alert variant="danger">
        Missing setup link. Submit the short form again or contact your franchise team.
      </Alert>
    );
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await setupPassword(token, password, passwordConfirmation);
    } catch (err) {
      if (err instanceof ApiError && err.body && typeof err.body === 'object' && err.body !== null && 'errors' in err.body) {
        const errors = (err.body as { errors?: Record<string, string[]> }).errors;
        setError(errors?.token?.[0] ?? errors?.password?.[0] ?? 'Setup failed.');
      } else {
        setError('Setup failed.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Card className={loginShell} padding="md">
      <div className={loginInner}>
        <h1 className="text-2xl font-semibold text-foreground">Create your password</h1>
        <p className="mt-2 text-sm text-content-secondary">
          Set a password to access and complete your franchise application.
        </p>

        {error ? (
          <Alert variant="danger" className="mt-4">
            {error}
          </Alert>
        ) : null}

        <form onSubmit={onSubmit} className="mt-6 space-y-4">
          <FormField
            label="Password"
            id="portal-setup-password"
            type="password"
            autoComplete="new-password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />
          <FormField
            label="Confirm password"
            id="portal-setup-password-confirm"
            type="password"
            autoComplete="new-password"
            value={passwordConfirmation}
            onChange={(event) => setPasswordConfirmation(event.target.value)}
            required
          />
          <Button type="submit" disabled={submitting} className="w-full">
            {submitting ? 'Saving…' : 'Continue to application'}
          </Button>
        </form>
      </div>
    </Card>
  );
}
