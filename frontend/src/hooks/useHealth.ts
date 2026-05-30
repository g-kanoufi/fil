import { useEffect, useState } from 'react';
import { fetchHealth, type HealthResponse } from '@/lib/api/health';

type HealthState =
  | { status: 'loading' }
  | { status: 'success'; data: HealthResponse }
  | { status: 'error'; error: Error };

export function useHealth(): HealthState {
  const [state, setState] = useState<HealthState>({ status: 'loading' });

  useEffect(() => {
    let cancelled = false;

    fetchHealth()
      .then((data) => {
        if (!cancelled) {
          setState({ status: 'success', data });
        }
      })
      .catch((error: unknown) => {
        if (!cancelled) {
          const err = error instanceof Error ? error : new Error('Health check failed');
          setState({ status: 'error', error: err });
        }
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return state;
}

export function formatHealthLabel(state: HealthState): string {
  if (state.status === 'loading') {
    return 'loading';
  }

  if (state.status === 'error') {
    return 'offline';
  }

  return `${state.data.service}: ${state.data.status}`;
}
