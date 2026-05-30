import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';

interface EmptyStateProps {
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
  size?: 'page' | 'compact';
}

export function EmptyState({
  title,
  description,
  action,
  className,
  size = 'page',
}: EmptyStateProps) {
  const compact = size === 'compact';

  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-surface text-center',
        compact ? 'px-4 py-6' : 'px-6 py-14',
        className,
      )}
    >
      <svg
        aria-hidden
        viewBox="0 0 48 48"
        className={cn('text-muted opacity-60', compact ? 'mb-2 h-8 w-8' : 'mb-4 h-12 w-12')}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
      >
        <rect x="8" y="10" width="32" height="28" rx="3" />
        <path d="M16 20h16M16 26h10M16 32h14" strokeLinecap="round" />
      </svg>
      <h3 className={cn('font-medium text-foreground', compact ? 'text-sm' : 'text-base')}>{title}</h3>
      {description ? (
        <p className={cn('mt-1.5 max-w-sm text-muted', compact ? 'text-xs' : 'text-sm')}>{description}</p>
      ) : null}
      {action ? <div className={compact ? 'mt-2' : 'mt-4'}>{action}</div> : null}
    </div>
  );
}
