import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { act, renderHook } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { normalizeAppPath, usePageActivityTracker } from '@/hooks/usePageActivityTracker';
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

describe('normalizeAppPath', () => {
  it('strips /app basename prefix', () => {
    expect(normalizeAppPath('/app/reports/leads/1')).toBe('/reports/leads/1');
    expect(normalizeAppPath('/app/history')).toBe('/history');
  });
});

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

    vi.advanceTimersByTime(3_000);

    expect(activityApi.postActivityPageViews).toHaveBeenCalledWith(['/reports/leads/42']);
  });

  it('posts normalized path when router includes /app prefix', () => {
    renderWithPath('/app/history');

    vi.advanceTimersByTime(3_000);

    expect(activityApi.postActivityPageViews).toHaveBeenCalledWith(['/history']);
  });

  it('does not post grid list paths', () => {
    renderWithPath('/reports/leads');

    vi.advanceTimersByTime(3_000);

    expect(activityApi.postActivityPageViews).not.toHaveBeenCalled();
  });

  it('records a second unit visit within 15 seconds of the first', async () => {
    const router = createMemoryRouter(
      [{ path: '*', element: <TrackerHarness /> }],
      { initialEntries: ['/app/reports/stores/11'] },
    );

    renderHook(() => usePageActivityTracker(), {
      wrapper: () => <RouterProvider router={router} />,
    });

    vi.advanceTimersByTime(3_000);

    expect(activityApi.postActivityPageViews).toHaveBeenCalledWith(['/reports/stores/11']);
    expect(activityApi.postActivityPageViews).toHaveBeenCalledTimes(1);

    await act(async () => {
      await router.navigate('/app/reports/stores/12');
    });

    vi.advanceTimersByTime(3_000);

    expect(activityApi.postActivityPageViews).toHaveBeenCalledWith(['/reports/stores/12']);
    expect(activityApi.postActivityPageViews).toHaveBeenCalledTimes(2);
  });

  it('flushes the previous detail page when navigating away before debounce', async () => {
    const router = createMemoryRouter(
      [{ path: '*', element: <TrackerHarness /> }],
      { initialEntries: ['/app/reports/stores/11'] },
    );

    renderHook(() => usePageActivityTracker(), {
      wrapper: () => <RouterProvider router={router} />,
    });

    vi.advanceTimersByTime(1_000);

    await act(async () => {
      await router.navigate('/app/reports/stores/12');
    });

    vi.advanceTimersByTime(3_000);

    expect(activityApi.postActivityPageViews).toHaveBeenCalledWith(['/reports/stores/11']);
    expect(activityApi.postActivityPageViews).toHaveBeenCalledWith(['/reports/stores/12']);
    expect(activityApi.postActivityPageViews).toHaveBeenCalledTimes(2);
  });
});
