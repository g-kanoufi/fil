import { renderHook, act } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { ReactNode } from 'react';
import { useGridDetailPanel } from '@/lib/grid/useGridDetailPanel';

function createWrapper(initialEntries: string[]) {
  return function Wrapper({ children }: { children: ReactNode }) {
    return (
      <MemoryRouter initialEntries={initialEntries}>
        <Routes>
          <Route path="/reports/leads" element={children} />
        </Routes>
      </MemoryRouter>
    );
  };
}

describe('useGridDetailPanel', () => {
  it('reads panel id from search params', () => {
    const { result } = renderHook(() => useGridDetailPanel(), {
      wrapper: createWrapper(['/reports/leads?panel=42&filter=open']),
    });

    expect(result.current.panelId).toBe(42);
    expect(result.current.isOpen).toBe(true);
  });

  it('opens and closes panel via search params', () => {
    const { result } = renderHook(() => useGridDetailPanel(), {
      wrapper: createWrapper(['/reports/leads']),
    });

    act(() => {
      result.current.openPanel(7);
    });

    expect(result.current.panelId).toBe(7);

    act(() => {
      result.current.closePanel();
    });

    expect(result.current.panelId).toBeNull();
    expect(result.current.isOpen).toBe(false);
  });
});
