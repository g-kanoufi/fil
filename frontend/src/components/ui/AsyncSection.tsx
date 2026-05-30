import type { ReactNode } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { LoadingState } from '@/components/ui/LoadingState';

interface AsyncSectionProps {
  status: 'loading' | 'error' | 'empty' | 'ready';
  loadingLabel?: string;
  error?: string | null;
  onRetry?: () => void;
  emptyTitle?: string;
  emptyDescription?: string;
  children: ReactNode;
}

export function AsyncSection({
  status,
  loadingLabel = 'Loading…',
  error,
  onRetry,
  emptyTitle = 'Nothing here yet',
  emptyDescription,
  children,
}: AsyncSectionProps) {
  if (status === 'loading') {
    return <LoadingState label={loadingLabel} className="min-h-[6rem]" />;
  }

  if (status === 'error') {
    return (
      <div className="space-y-3">
        <Alert variant="error">{error ?? 'Something went wrong.'}</Alert>
        {onRetry ? (
          <Button type="button" size="sm" variant="secondary" onClick={onRetry}>
            Try again
          </Button>
        ) : null}
      </div>
    );
  }

  if (status === 'empty') {
    return <EmptyState size="compact" title={emptyTitle} description={emptyDescription} />;
  }

  return <>{children}</>;
}
