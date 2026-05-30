import { useEffect, useRef, type RefObject } from 'react';
import { getFocusableElements, trapTabKey } from '@/lib/a11y/focusTrap';

interface UseDialogA11yOptions {
  /** When true, Tab cycles within the panel. Defaults to true. */
  trapFocus?: boolean;
  /** When true, Escape calls onClose. Defaults to true. */
  closeOnEscape?: boolean;
  /** When true, locks body scroll while open. Defaults to true. */
  lockScroll?: boolean;
}

export function useDialogA11y(
  open: boolean,
  onClose: () => void,
  panelRef: RefObject<HTMLElement | null>,
  options: UseDialogA11yOptions = {},
): void {
  const { trapFocus = true, closeOnEscape = true, lockScroll = true } = options;
  const triggerRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    triggerRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    const previousOverflow = document.body.style.overflow;

    if (lockScroll) {
      document.body.style.overflow = 'hidden';
    }

    const panel = panelRef.current;

    if (panel) {
      requestAnimationFrame(() => {
        const focusable = getFocusableElements(panel);
        if (focusable.length > 0) {
          focusable[0].focus();
        } else {
          panel.focus();
        }
      });
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (closeOnEscape && event.key === 'Escape') {
        event.preventDefault();
        onClose();
        return;
      }

      if (trapFocus && panelRef.current) {
        trapTabKey(event, panelRef.current);
      }
    };

    window.addEventListener('keydown', onKeyDown);

    return () => {
      if (lockScroll) {
        document.body.style.overflow = previousOverflow;
      }

      window.removeEventListener('keydown', onKeyDown);
      triggerRef.current?.focus();
    };
  }, [open, onClose, panelRef, trapFocus, closeOnEscape, lockScroll]);
}
