import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { renderHook } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import type { ReactNode } from 'react';
import { usePageActivityTracker } from '@/hooks/usePageActivityTracker';
import * as activityApi from '@/lib/api/activity';

vi.mock('@/lib/api/activity', () => ({
  postActivityPageViews: vi.fn().mockResolvedValue({ accepted: true }),
}));

function renderWithPath(path: string) {
  const router = createMemoryRouter(
    [{ path: '*', element: <TrackerHarness /> }],
    { initialEntries: [path] },
  );

  return renderHook(() => usePageActivityTracker(), {
    wrapper: () => <RouterProvider router={router} />,
  });
}

function TrackerHarness(): null {
  usePageActivityTracker();

  return null;
}

describe('usePageActivityTracker', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    sessionStorage.clear();
    vi.mocked(activityApi.postActivityPageViews).mockClear();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('debounces and posts allowlisted detail paths', () => {
    renderWithPath('/reports/leads/42');

    vi.advanceTimersByTime(10_000);

    expect(activityApi.postActivityPageViews).toHaveBeenCalledWith(['/reports/leads/42']);
  });

  it('does not post grid list paths', () => {
    renderWithPath('/reports/leads');

    vi.advanceTimersByTime(10_000);

    expect(activityApi.postActivityPageViews).not.toHaveBeenCalled();
  });
});
