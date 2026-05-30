import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { badge } from '@/lib/ui/tokens';

type BadgeVariant = keyof typeof badge;

interface BadgeProps {
  children: ReactNode;
  variant?: BadgeVariant;
  className?: string;
}

export function Badge({ children, variant = 'default', className }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
        badge[variant],
        className,
      )}
    >
      {children}
    </span>
  );
}
