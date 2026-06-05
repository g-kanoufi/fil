import { FormEvent, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { SignInPageShell } from '@/components/auth/SignInPageShell';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { ApiError } from '@/lib/api/client';
import { usePortalAuth } from '@/providers/PortalAuthProvider';

export function PortalLoginPage() {
  const { status, login } = usePortalAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  if (status === 'authenticated') {
    return <Navigate to="/application" replace />;
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await login(email, password);
    } catch (err) {
      if (err instanceof ApiError && err.body && typeof err.body === 'object' && err.body !== null && 'errors' in err.body) {
        const errors = (err.body as { errors?: Record<string, string[]> }).errors;
        setError(errors?.email?.[0] ?? 'Sign in failed.');
      } else {
        setError('Sign in failed.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <SignInPageShell
      title="Prospect sign in"
      subtitle="Continue your franchise application."
      headingId="portal-login-heading"
      devHint={
        import.meta.env.DEV ? (
          <Alert variant="info" className="mb-4">
            Demo: <code className="text-xs">prospect@fil.test</code> — password{' '}
            <code className="text-xs">password</code> (linked to Jane Smith Application)
          </Alert>
        ) : null
      }
    >
      <form onSubmit={onSubmit} className="space-y-4" aria-labelledby="portal-login-heading">
        <FormField
          label="Email"
          id="portal-email"
          type="email"
          autoComplete="username"
          autoFocus
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
        <FormField
          label="Password"
          id="portal-password"
          type="password"
          autoComplete="current-password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
        />
        {error ? <Alert variant="error">{error}</Alert> : null}
        <Button type="submit" disabled={submitting} className="w-full">
          {submitting ? 'Signing in…' : 'Sign in'}
        </Button>
      </form>
    </SignInPageShell>
  );
}
