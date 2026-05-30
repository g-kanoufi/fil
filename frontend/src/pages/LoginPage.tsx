import { FormEvent, useState } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { BrandMark } from '@/components/branding/BrandMark';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/FormField';
import { ApiError } from '@/lib/api/client';
import { loginInner, loginShell } from '@/lib/ui/tokens';
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
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-b from-accent-soft/50 to-[var(--body-bg-color)] px-4 py-10">
      <div className={loginShell}>
        <Card className={`w-full ${loginInner}`} padding="md">
          <div className="mb-6 text-center">
            <BrandMark variant="login" />
            <h1 className="mt-2 text-2xl font-semibold text-foreground">Staff sign in</h1>
            <p className="mt-1 text-sm text-muted">Franchise operations portal</p>
          </div>

          {import.meta.env.DEV ? (
            <Alert variant="info" className="mb-4">
              Demo: <code className="text-xs">admin@fil.test</code>,{' '}
              <code className="text-xs">franchisor@fil.test</code>,{' '}
              <code className="text-xs">franchisee@fil.test</code>,{' '}
              <code className="text-xs">area_rep@fil.test</code> — password{' '}
              <code className="text-xs">password</code>
            </Alert>
          ) : null}

          <form onSubmit={onSubmit} className="space-y-4">
            <FormField
              label="Email"
              id="email"
              type="email"
              autoComplete="username"
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
        </Card>
      </div>
    </div>
  );
}
