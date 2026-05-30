import { useEffect } from 'react';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { GridPage } from '@/pages/GridPage';

const mockAppConfig = {
  options: { brandName: 'FIL' },
  menus: {
    top_menus: [],
    menus_with_columns: {
      stores: {
        menuItems: {
          store_status: { label: 'Unit statuses', slug: 'store_status' },
          store_area: { label: 'Areas', slug: 'store_area' },
        },
        subMenuItems: {
          store_status: {
            open: { label: 'Open', slug: 'open' },
          },
          store_area: {
            '1': { label: 'North Region', slug: '1' },
          },
        },
      },
    },
  },
};

const mockAuthState = vi.hoisted(() => ({
  user_has_panel_access: true,
}));

vi.mock('@/providers/AuthProvider', () => ({
  useAuth: () => ({
    appConfig: mockAppConfig,
    isUiDisabled: () => false,
    can: () => true,
    user: { user_has_panel_access: mockAuthState.user_has_panel_access },
  }),
}));

vi.mock('@/components/grid/GridToolbar', () => ({
  GridToolbar: () => <div data-testid="grid-toolbar" />,
  RecordCountChip: ({ total }: { total: number }) => <div data-testid="record-count">{total}</div>,
}));

vi.mock('@/components/search/AiSearchField', () => ({
  AiSearchSummary: () => null,
}));

vi.mock('@/components/detail/GridDetailPanel', () => ({
  GridDetailPanel: ({ open, recordId }: { open: boolean; recordId: number | null }) =>
    open && recordId ? <div data-testid="grid-detail-panel">{recordId}</div> : null,
}));

vi.mock('@/components/grid/FilGrid', () => ({
  FilGrid: ({
    onAggregations,
    onTotalChange,
    onRowNavigate,
  }: {
    onAggregations?: (aggregations: Record<string, unknown>) => void;
    onTotalChange?: (total: number) => void;
    onRowNavigate?: (id: number) => void;
  }) => {
    useEffect(() => {
      onAggregations?.({
        'meta.stores': { buckets: [{ key: 'open', doc_count: 2 }] },
        'meta.areas': { buckets: [{ key: '1', label: 'North Region', doc_count: 2 }] },
      });
      onTotalChange?.(2);
    }, [onAggregations, onTotalChange]);

    return (
      <div data-testid="fil-grid">
        <button type="button" onClick={() => onRowNavigate?.(42)}>
          Simulate row click
        </button>
      </div>
    );
  },
}));

function renderStoresGrid(initialPath = '/reports/stores') {
  const router = createMemoryRouter(
    [
      { path: '/reports/stores', element: <GridPage title="Stores" resource="stores" /> },
      { path: '/reports/stores/:id', element: <div data-testid="store-detail-page" /> },
    ],
    { initialEntries: [initialPath] },
  );

  render(<RouterProvider router={router} />);

  return router;
}

describe('GridPage', () => {
  beforeEach(() => {
    mockAuthState.user_has_panel_access = true;
  });

  it('renders store area filter chips with counts', async () => {
    renderStoresGrid();

    expect(await screen.findByRole('button', { name: /North Region/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Open/i })).toBeInTheDocument();
    expect(screen.getByTestId('record-count')).toHaveTextContent('2');
    expect(screen.getByTestId('fil-grid')).toBeInTheDocument();
  });

  it('updates URL when an area filter chip is selected', async () => {
    const router = renderStoresGrid();

    fireEvent.click(await screen.findByRole('button', { name: /North Region/i }));

    expect(router.state.location.search).toContain('filter=store_area');
    expect(router.state.location.search).toContain('subFilter=1');
  });

  it('opens panel on row click when user has panel access', async () => {
    const router = renderStoresGrid();

    fireEvent.click(await screen.findByRole('button', { name: 'Simulate row click' }));

    expect(router.state.location.search).toContain('panel=42');
    expect(screen.getByTestId('grid-detail-panel')).toHaveTextContent('42');
  });

  it('navigates to full page on row click when user lacks panel access', async () => {
    mockAuthState.user_has_panel_access = false;
    const router = renderStoresGrid();

    fireEvent.click(await screen.findByRole('button', { name: 'Simulate row click' }));

    expect(router.state.location.pathname).toBe('/reports/stores/42');
    expect(router.state.location.search).not.toContain('panel=');
    expect(screen.queryByTestId('grid-detail-panel')).not.toBeInTheDocument();
  });

  it('strips panel param and does not mount panel when user lacks panel access', async () => {
    mockAuthState.user_has_panel_access = false;
    const router = renderStoresGrid('/reports/stores?panel=7&filter=store_status');

    await waitFor(() => {
      expect(router.state.location.search).not.toContain('panel=');
    });

    expect(router.state.location.search).toContain('filter=store_status');
    expect(screen.queryByTestId('grid-detail-panel')).not.toBeInTheDocument();
  });
});
