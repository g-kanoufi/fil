import type { ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { alert } from '@/lib/ui/tokens';

type AlertVariant = keyof typeof alert;

interface AlertProps {
  children: ReactNode;
  variant?: AlertVariant;
  className?: string;
}

export function Alert({ children, variant = 'info', className }: AlertProps) {
  return (
    <div
      role={variant === 'error' ? 'alert' : 'status'}
      className={cn('rounded-lg px-4 py-3 text-sm', alert[variant], className)}
    >
      {children}
    </div>
  );
}
