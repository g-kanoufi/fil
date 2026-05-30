import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';

interface EmptyStateProps {
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
}

export function EmptyState({ title, description, action, className }: EmptyStateProps) {
  return (
    <div
      className={cn(
        'flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-surface px-6 py-14 text-center',
        className,
      )}
    >
      <svg
        aria-hidden
        viewBox="0 0 48 48"
        className="mb-4 h-12 w-12 text-muted opacity-60"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
      >
        <rect x="8" y="10" width="32" height="28" rx="3" />
        <path d="M16 20h16M16 26h10M16 32h14" strokeLinecap="round" />
      </svg>
      <h3 className="text-base font-medium text-foreground">{title}</h3>
      {description ? <p className="mt-1.5 max-w-sm text-sm text-muted">{description}</p> : null}
      {action ? <div className="mt-4">{action}</div> : null}
    </div>
  );
}
