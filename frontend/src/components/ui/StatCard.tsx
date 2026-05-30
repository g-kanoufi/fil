import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { surface } from '@/lib/ui/tokens';

const accentClasses = {
  brand: surface.statBrandAccent,
  success: 'border border-tone-success-border bg-tone-success-bg text-tone-success-fg',
  neutral: 'border-border bg-surface text-foreground',
};

const accentLabelClasses = {
  brand: 'text-sm font-medium text-accent-soft-muted',
  success: 'text-sm font-medium text-tone-success-fg/80',
  neutral: 'text-sm font-medium text-muted',
};

export function StatCard({ label, value, hint, accent = 'neutral' }: StatCardProps) {
  return (
    <div
      className={cn(
        'rounded-xl border p-5 shadow-[var(--shadow-card)]',
        accentClasses[accent],
      )}
    >
      <p className={accentLabelClasses[accent]}>{label}</p>
      <p className="mt-2 text-3xl font-semibold tracking-tight text-foreground">{value}</p>
      {hint ? <p className="mt-1 text-xs text-muted">{hint}</p> : null}
    </div>
  );
}
