import { FormEvent, useState } from 'react';
import { Navigate, useSearchParams } from 'react-router-dom';
import { SignInPageShell } from '@/components/auth/SignInPageShell';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { ApiError } from '@/lib/api/client';
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
      <SignInPageShell
        title="Create your password"
        subtitle="Set a password to access and complete your franchise application."
      >
        <Alert variant="error">
          Missing setup link. Submit the short form again or contact your franchise team.
        </Alert>
      </SignInPageShell>
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
    <SignInPageShell
      title="Create your password"
      subtitle="Set a password to access and complete your franchise application."
      headingId="portal-setup-heading"
    >
      <form onSubmit={onSubmit} className="space-y-4" aria-labelledby="portal-setup-heading">
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
        {error ? <Alert variant="error">{error}</Alert> : null}
        <Button type="submit" disabled={submitting} className="w-full">
          {submitting ? 'Saving…' : 'Continue to application'}
        </Button>
      </form>
    </SignInPageShell>
  );
}
