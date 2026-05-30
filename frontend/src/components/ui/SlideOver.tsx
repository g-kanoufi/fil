import { useEffect, useId, useRef, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { cn } from '@/lib/cn';
import { focusRing } from '@/lib/ui/tokens';

interface SlideOverProps {
  open: boolean;
  onClose: () => void;
  /** Accessible name when no visible title is passed */
  ariaLabel?: string;
  title?: ReactNode;
  description?: ReactNode;
  headerActions?: ReactNode;
  children: ReactNode;
  className?: string;
}

export function SlideOver({
  open,
  onClose,
  ariaLabel = 'Record detail',
  title,
  description,
  headerActions,
  children,
  className,
}: SlideOverProps) {
  const titleId = useId();
  const descriptionId = useId();
  const panelRef = useRef<HTMLElement>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose();
      }
    };

    window.addEventListener('keydown', onKeyDown);
    panelRef.current?.focus();

    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener('keydown', onKeyDown);
    };
  }, [open, onClose]);

  if (!open) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex justify-end">
      <button
        type="button"
        className="absolute inset-0 bg-foreground/20 backdrop-blur-[1px]"
        aria-label="Close detail panel"
        onClick={onClose}
      />

      <aside
        ref={panelRef}
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        aria-label={title ? undefined : ariaLabel}
        aria-labelledby={title ? titleId : undefined}
        aria-describedby={description ? descriptionId : undefined}
        className={cn(
          'relative flex h-full w-full max-w-[min(100vw,42rem)] flex-col border-l border-border bg-surface shadow-[var(--shadow-elevated)] animate-slide-in-right',
          className,
        )}
      >
        {title || headerActions ? (
          <header className="flex shrink-0 items-start justify-between gap-3 border-b border-border px-4 py-3 sm:px-5">
            <div className="min-w-0">
              {title ? (
                <h2 id={titleId} className="truncate text-lg font-semibold text-foreground">
                  {title}
                </h2>
              ) : null}
              {description ? (
                <p id={descriptionId} className="mt-0.5 text-sm text-muted">
                  {description}
                </p>
              ) : null}
            </div>
            {headerActions ? <div className="flex shrink-0 items-center gap-2">{headerActions}</div> : null}
          </header>
        ) : null}

        <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">{children}</div>
      </aside>
    </div>
  );
}

interface PanelChromeProps {
  title: string;
  onClose: () => void;
  openFullPageHref: string;
  openFullPageLabel?: string;
}

export function DetailPanelChrome({
  title,
  onClose,
  openFullPageHref,
  openFullPageLabel = 'Open full page',
}: PanelChromeProps) {
  return (
    <div className="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
      <h2 className="min-w-0 truncate text-lg font-semibold text-foreground">{title}</h2>
      <div className="flex shrink-0 items-center gap-2">
        <Link
          to={openFullPageHref}
          className={cn(
            'inline-flex items-center rounded-lg border border-border bg-surface px-3 py-1.5 text-sm font-medium text-foreground transition-colors hover:bg-surface-muted',
            focusRing,
          )}
        >
          {openFullPageLabel}
        </Link>
        <button
          type="button"
          onClick={onClose}
          className={cn(
            'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-muted transition-colors hover:bg-surface-muted hover:text-foreground',
            focusRing,
          )}
        >
          Close
        </button>
      </div>
    </div>
  );
}
