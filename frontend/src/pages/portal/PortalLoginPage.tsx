import { FormEvent, useState } from 'react';
import { Navigate } from 'react-router-dom';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { ApiError } from '@/lib/api/client';
import { loginInner, loginShell } from '@/lib/ui/tokens';
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
    <Card className={loginShell} padding="md">
      <div className={loginInner}>
        <h1 className="text-2xl font-semibold text-foreground">Prospect sign in</h1>
        <p className="mt-2 text-sm text-content-secondary">
          Continue your franchise application.
        </p>

        {error ? (
          <Alert variant="danger" className="mt-4">
            {error}
          </Alert>
        ) : null}

        <form onSubmit={onSubmit} className="mt-6 space-y-4">
          <FormField
            label="Email"
            id="portal-email"
            type="email"
            autoComplete="username"
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
          <Button type="submit" disabled={submitting} className="w-full">
            {submitting ? 'Signing in…' : 'Sign in'}
          </Button>
        </form>
      </div>
    </Card>
  );
}
