import { LoadingState } from '@/components/ui/LoadingState';

export function RouteFallback() {
  return <LoadingState label="Loading page…" className="min-h-[40vh]" />;
}
