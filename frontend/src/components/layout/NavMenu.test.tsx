import { render, screen } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import { NavMenu } from '@/components/layout/NavMenu';
import type { NavItem } from '@/types/auth';

const activeRowClass = 'bg-[var(--color-sidebar-active)]';

const submenuItems: NavItem[] = [
  {
    id: 'stores',
    label: 'Stores',
    path: '/reports/stores',
    children: [
      {
        id: 'stores-store_status',
        label: 'Status',
        path: '/reports/stores?filter=store_status',
      },
      {
        id: 'stores-store_area',
        label: 'Area',
        path: '/reports/stores?filter=store_area',
      },
    ],
  },
];

function renderNavMenu(initialEntry: string) {
  const router = createMemoryRouter(
    [
      {
        path: '/reports/stores',
        element: <NavMenu items={submenuItems} />,
      },
    ],
    { initialEntries: [initialEntry] },
  );

  render(<RouterProvider router={router} />);
}

describe('NavMenu', () => {
  it('highlights only the submenu item matching the current filter', () => {
    renderNavMenu('/reports/stores?filter=store_status');

    const statusLink = screen.getByRole('link', { name: 'Status' });
    const areaLink = screen.getByRole('link', { name: 'Area' });

    expect(statusLink.className).toContain(activeRowClass);
    expect(areaLink.className).not.toContain(activeRowClass);
  });
});
