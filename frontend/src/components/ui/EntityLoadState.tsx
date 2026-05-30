import type { ReactNode } from 'react';
import { TextLink } from '@/components/ui/TextLink';
import { Alert } from '@/components/ui/Alert';
import { LoadingState } from '@/components/ui/LoadingState';

interface EntityLoadStateProps {
  loading: boolean;
  found: boolean;
  error?: string | null;
  loadingLabel: string;
  notFoundMessage?: string;
  layout?: 'page' | 'panel';
  backTo?: { label: string; href: string };
  notFoundPrefix?: ReactNode;
  children: ReactNode;
}

export function EntityLoadState({
  loading,
  found,
  error,
  loadingLabel,
  notFoundMessage = 'Not found',
  layout = 'page',
  backTo,
  notFoundPrefix,
  children,
}: EntityLoadStateProps) {
  if (loading) {
    return <LoadingState label={loadingLabel} />;
  }

  if (!found) {
    const message = error ?? notFoundMessage;

    if (layout === 'panel') {
      return <Alert variant="error">{message}</Alert>;
    }

    return (
      <>
        {notFoundPrefix}
        <Alert variant="error">{message}</Alert>
        {backTo ? (
          <TextLink to={backTo.href} plain className="mt-4 inline-block text-sm">
            {backTo.label}
          </TextLink>
        ) : null}
      </>
    );
  }

  return <>{children}</>;
}
