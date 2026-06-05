import type { ReactNode } from 'react';
import { BrandMark } from '@/components/branding/BrandMark';
import { Card } from '@/components/ui/Card';
import { loginInner, loginShell } from '@/lib/ui/tokens';

interface SignInPageShellProps {
  title: string;
  subtitle?: string;
  headingId?: string;
  devHint?: ReactNode;
  children: ReactNode;
}

/** Full-viewport sign-in frame shared by staff and prospect portal. */
export function SignInPageShell({
  title,
  subtitle,
  headingId,
  devHint,
  children,
}: SignInPageShellProps) {
  return (
    <main
      id="main-content"
      className="flex min-h-screen items-center justify-center bg-gradient-to-b from-accent-soft/50 to-[var(--body-bg-color)] px-4 py-10"
    >
      <div className={loginShell}>
        <Card className={`w-full ${loginInner}`} padding="md">
          <div className="mb-6 text-center">
            <BrandMark variant="login" />
            <h1
              id={headingId}
              className="mt-2 text-2xl font-semibold text-foreground"
            >
              {title}
            </h1>
            {subtitle ? (
              <p className="mt-2 text-sm text-content-secondary">{subtitle}</p>
            ) : null}
          </div>

          {devHint}

          {children}
        </Card>
      </div>
    </main>
  );
}
