import { FormEvent, useState } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { SignInPageShell } from '@/components/auth/SignInPageShell';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/FormField';
import { ApiError } from '@/lib/api/client';
import { useAuth } from '@/providers/AuthProvider';

export function LoginPage() {
  const { status, login } = useAuth();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const redirectTo =
    (location.state as { from?: string } | null)?.from && (location.state as { from?: string }).from !== '/login'
      ? (location.state as { from: string }).from
      : '/';

  if (status === 'authenticated') {
    return <Navigate to={redirectTo} replace />;
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
        setError(errors?.email?.[0] ?? 'Login failed.');
      } else {
        setError('Login failed.');
      }
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <SignInPageShell
      title="Sign in"
      headingId="login-heading"
      devHint={
        import.meta.env.DEV ? (
          <Alert variant="info" className="mb-4">
            Demo: <code className="text-xs">admin@fil.test</code>,{' '}
            <code className="text-xs">franchisor@fil.test</code>,{' '}
            <code className="text-xs">franchisee@fil.test</code>,{' '}
            <code className="text-xs">area_rep@fil.test</code> — password{' '}
            <code className="text-xs">password</code>
          </Alert>
        ) : null
      }
    >
      <form onSubmit={onSubmit} className="space-y-4" aria-labelledby="login-heading">
        <FormField
          label="Email"
          id="email"
          type="email"
          autoComplete="username"
          autoFocus
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          required
        />
        <FormField
          label="Password"
          id="password"
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
