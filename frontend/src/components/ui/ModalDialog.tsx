import { useRef, type ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { useDialogA11y } from '@/lib/a11y/useDialogA11y';

interface ModalDialogProps {
  open: boolean;
  onClose: () => void;
  /** Must match the id on the dialog title element inside children. */
  labelledBy: string;
  /** Optional id for the description element inside children. */
  describedBy?: string;
  /** Used when no visible title is rendered. */
  ariaLabel?: string;
  children: ReactNode;
  className?: string;
  backdropClassName?: string;
}

export function ModalDialog({
  open,
  onClose,
  labelledBy,
  describedBy,
  ariaLabel,
  children,
  className,
  backdropClassName,
}: ModalDialogProps) {
  const panelRef = useRef<HTMLDivElement>(null);

  useDialogA11y(open, onClose, panelRef);

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <button
        type="button"
        aria-label="Close dialog"
        className={cn('absolute inset-0 bg-black/40', backdropClassName)}
        onClick={onClose}
      />

      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-label={ariaLabel}
        aria-labelledby={ariaLabel ? undefined : labelledBy}
        aria-describedby={describedBy}
        tabIndex={-1}
        className={cn(
          'relative w-full max-w-lg rounded-xl border border-border bg-surface p-6 shadow-xl',
          className,
        )}
      >
        {children}
      </div>
    </div>
  );
}
