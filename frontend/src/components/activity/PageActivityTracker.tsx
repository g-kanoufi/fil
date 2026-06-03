import { usePageActivityTracker } from '@/hooks/usePageActivityTracker';

/** Records staff SPA page visits (debounced, allowlisted). */
export function PageActivityTracker() {
  usePageActivityTracker();

  return null;
}
