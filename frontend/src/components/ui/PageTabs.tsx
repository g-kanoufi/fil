import { cn } from '@/lib/cn';
import { focusRing } from '@/lib/ui/tokens';

export interface PageTabItem {
  id: string;
  label: string;
}

interface PageTabsProps {
  items: PageTabItem[];
  value: string;
  onChange: (id: string) => void;
  /** Accessible name for the tab list, e.g. "Documents view" */
  ariaLabel: string;
  className?: string;
}

/**
 * Underline page tabs (fl-react parity — clearly tabs, not primary/secondary buttons).
 * Use with `role="tabpanel"` regions keyed by the active tab id.
 */
export function PageTabs({ items, value, onChange, ariaLabel, className }: PageTabsProps) {
  return (
    <div className={cn('mb-6 border-b border-border', className)}>
      <div role="tablist" aria-label={ariaLabel} className="-mb-px flex flex-wrap gap-0.5">
        {items.map((item) => {
          const selected = value === item.id;

          return (
            <button
              key={item.id}
              type="button"
              role="tab"
              id={`tab-${item.id}`}
              aria-selected={selected}
              aria-controls={`tabpanel-${item.id}`}
              tabIndex={selected ? 0 : -1}
              onClick={() => onChange(item.id)}
              className={cn(
                'relative shrink-0 px-4 py-2.5 text-sm font-medium transition-colors',
                focusRing,
                selected
                  ? 'text-primary after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:rounded-full after:bg-primary'
                  : 'text-muted hover:text-foreground',
              )}
            >
              {item.label}
            </button>
          );
        })}
      </div>
    </div>
  );
}
