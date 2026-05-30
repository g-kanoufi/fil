import { cn } from '@/lib/cn';
import { chipButton, metricLabel, metricValueActive, metricValueInactive, surface } from '@/lib/ui/tokens';
import type { GridBucket } from '@/lib/api/grid';

const TEMP_OPTIONS = [
  { hash: '', label: 'All Leads' },
  { hash: 'hot', label: 'Hot Leads' },
  { hash: 'warm', label: 'Warm Leads' },
  { hash: 'cold', label: 'Cold Leads' },
];

interface LeadsTempFilterProps {
  total: number;
  active: string;
  buckets: GridBucket[];
  loading?: boolean;
  onChange: (value: string) => void;
}

export function LeadsTempFilter({ total, active, buckets, loading, onChange }: LeadsTempFilterProps) {
  return (
    <div className="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
      {TEMP_OPTIONS.map(({ hash, label }) => {
        const bucket = buckets.find((item) => String(item.key ?? '').toLowerCase() === hash);
        const count = hash === '' ? total : bucket?.doc_count ?? 0;

        return (
          <button
            key={hash || 'all'}
            type="button"
            onClick={() => onChange(hash)}
            className={cn(
              chipButton,
              active === hash
                ? surface.accentSelected
                : 'border-border bg-surface hover:border-primary hover:bg-accent-soft',
            )}
          >
            <div className={active === hash ? metricValueActive : metricValueInactive}>
              {loading ? '…' : count.toLocaleString()}
            </div>
            <div className={metricLabel}>{label}</div>
          </button>
        );
      })}
    </div>
  );
}
