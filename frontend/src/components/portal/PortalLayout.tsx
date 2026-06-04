import { Link, Outlet } from 'react-router-dom';
import { Button } from '@/components/ui/Button';
import { usePortalAuth } from '@/providers/PortalAuthProvider';
import { textLink } from '@/lib/ui/tokens';

export function PortalLayout() {
  const { user, logout } = usePortalAuth();

  return (
    <div className="min-h-screen bg-[var(--body-bg-color)] text-foreground">
      <header className="border-b border-border-subtle bg-surface-elevated px-6 py-4">
        <div className="mx-auto flex max-w-3xl items-center justify-between gap-4">
          <div>
            <p className="text-sm font-semibold uppercase tracking-wide text-muted">
              Franchise application
            </p>
            {user ? <p className="text-sm text-muted">{user.email}</p> : null}
          </div>
          {user ? (
            <Button type="button" variant="secondary" onClick={() => void logout()}>
              Sign out
            </Button>
          ) : (
            <Link className={textLink} to="/login">
              Sign in
            </Link>
          )}
        </div>
      </header>
      <main className="mx-auto max-w-3xl px-6 py-8">
        <Outlet />
      </main>
    </div>
  );
}
