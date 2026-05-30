import { useCallback, useEffect, useId, useRef, useState } from 'react';
import { cn } from '@/lib/cn';
import { surface, textLink, formFocus, button } from '@/lib/ui/tokens';
import { interpretGridSearch, type GridSearchInterpretation } from '@/lib/api/gridSearch';

interface AiSearchFieldProps {
  resource: 'leads' | 'stores' | 'contacts';
  value: string;
  onChange?: (value: string) => void;
  onInterpretation?: (result: GridSearchInterpretation) => void;
  placeholder?: string;
  debounceMs?: number;
  minLength?: number;
  className?: string;
  id?: string;
  label?: string;
  hideLabel?: boolean;
  autoInterpret?: boolean;
}

export function AiSearchField({
  resource,
  value,
  onChange,
  onInterpretation,
  placeholder = 'Ask in plain language — e.g. hot leads in waiting period',
  debounceMs = 650,
  minLength = 2,
  className,
  id,
  label = 'AI search',
  hideLabel = false,
  autoInterpret = true,
}: AiSearchFieldProps) {
  const generatedId = useId();
  const inputId = id ?? generatedId;
  const [localValue, setLocalValue] = useState(value);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const requestRef = useRef(0);

  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  const runInterpret = useCallback(
    async (query: string) => {
      const trimmed = query.trim();

      if (trimmed.length < minLength) {
        setError(trimmed.length === 1 ? 'Enter at least 2 characters to search.' : null);
        return;
      }

      const requestId = ++requestRef.current;
      setLoading(true);
      setError(null);

      try {
        const result = await interpretGridSearch(resource, trimmed);

        if (requestId !== requestRef.current) {
          return;
        }

        onChange?.(trimmed);
        onInterpretation?.(result);
      } catch (interpretError: unknown) {
        if (requestId !== requestRef.current) {
          return;
        }

        setError(
          interpretError instanceof Error ? interpretError.message : 'AI search failed. Try again.',
        );
      } finally {
        if (requestId === requestRef.current) {
          setLoading(false);
        }
      }
    },
    [minLength, onChange, onInterpretation, resource],
  );

  useEffect(() => {
    if (!autoInterpret) {
      return;
    }

    const trimmed = localValue.trim();

    if (trimmed.length < minLength) {
      setError(trimmed.length === 1 ? 'Enter at least 2 characters to search.' : null);
      return;
    }

    const timer = window.setTimeout(() => {
      if (trimmed !== value.trim()) {
        void runInterpret(trimmed);
      }
    }, debounceMs);

    return () => window.clearTimeout(timer);
  }, [autoInterpret, debounceMs, localValue, minLength, runInterpret, value]);

  return (
    <div className={cn('w-full', className)}>
      {!hideLabel ? (
        <label htmlFor={inputId} className="mb-1 block text-sm font-medium text-foreground">
          {label}
        </label>
      ) : (
        <label htmlFor={inputId} className="sr-only">
          {label}
        </label>
      )}

      <div className="relative">
        <span
          className={cn('pointer-events-none absolute inset-y-0 left-3 flex items-center text-link')}
          aria-hidden
        >
          <SparkleIcon className={cn('h-4 w-4', loading ? 'animate-pulse' : '')} />
        </span>

        <input
          id={inputId}
          type="search"
          value={localValue}
          placeholder={placeholder}
          onChange={(event) => setLocalValue(event.target.value)}
          onKeyDown={(event) => {
            if (event.key === 'Enter') {
              event.preventDefault();
              void runInterpret(localValue);
            }
          }}
          className={cn(
            'block w-full rounded-lg border border-border bg-surface py-2 pl-10 pr-24 text-sm',
            'placeholder:text-muted',
            formFocus,
          )}
        />

        <button
          type="button"
          onClick={() => void runInterpret(localValue)}
          disabled={loading || localValue.trim().length < minLength}
          className={cn(
            'absolute inset-y-1 right-1 rounded-md px-3 text-xs font-medium transition-colors',
            loading || localValue.trim().length < minLength
              ? 'cursor-not-allowed bg-surface-muted text-muted'
              : cn(button.primary, 'bg-primary hover:bg-primary-hover'),
          )}
        >
          {loading ? 'Thinking…' : 'Search'}
        </button>
      </div>

      {error ? <p className="mt-1.5 text-sm text-error">{error}</p> : null}
    </div>
  );
}

interface AiSearchSummaryProps {
  summary: string;
  resultCount?: number;
  source?: string;
  confidence?: string;
  onClear?: () => void;
  className?: string;
}

export function AiSearchSummary({
  summary,
  resultCount,
  source,
  confidence,
  onClear,
  className,
}: AiSearchSummaryProps) {
  if (!summary) {
    return null;
  }

  return (
    <div
      className={cn(
        'mb-4 flex flex-col gap-2 rounded-xl px-4 py-3 sm:flex-row sm:items-start sm:justify-between',
        surface.accentCallout,
        className,
      )}
      role="status"
    >
      <div className="flex gap-3">
        <SparkleIcon className="mt-0.5 h-5 w-5 shrink-0 text-link" />
        <div>
          <p className={surface.accentCalloutTitle}>{summary}</p>
          <p className={surface.accentCalloutMeta}>
            {typeof resultCount === 'number' ? `${resultCount.toLocaleString()} matching records` : null}
            {source ? ` · ${source === 'ai' ? 'AI interpreted' : 'Smart filter'}` : null}
            {confidence && confidence !== 'high' ? ` · ${confidence} confidence` : null}
          </p>
        </div>
      </div>

      {onClear ? (
        <button
          type="button"
          onClick={onClear}
          className={surface.accentCalloutAction}
        >
          Clear AI search
        </button>
      ) : null}
    </div>
  );
}

function SparkleIcon({ className }: { className?: string }) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      className={className}
      aria-hidden
    >
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M12 3l1.4 4.2L17.6 8 13.4 9.4 12 13.6 10.6 9.4 6.4 8l4.2-.8L12 3z"
      />
      <path strokeLinecap="round" strokeLinejoin="round" d="M5 14l.8 2.4L8.2 17l-2.4.8L5 20.2l-.8-2.4L1.8 17l2.4-.6L5 14z" />
      <path strokeLinecap="round" strokeLinejoin="round" d="M19 14l.8 2.4L22.2 17l-2.4.8L19 20.2l-.8-2.4L15.8 17l2.4-.6L19 14z" />
    </svg>
  );
}
