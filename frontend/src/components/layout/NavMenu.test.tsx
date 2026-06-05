import { render, screen } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import { NavMenu } from '@/components/layout/NavMenu';
import type { NavItem } from '@/types/auth';

const activeRowClass = 'bg-[var(--color-sidebar-active)]';

const submenuItems: NavItem[] = [
  {
    id: 'stores',
    label: 'Units',
    path: '/reports/stores',
    expandOnly: true,
    children: [
      {
        id: 'stores-open',
        label: 'Open',
        path: '/reports/stores?filter=store_status&subFilter=open',
      },
      {
        id: 'stores-pending',
        label: 'Pending',
        path: '/reports/stores?filter=store_status&subFilter=pending',
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
    renderNavMenu('/reports/stores?filter=store_status&subFilter=open');

    expect(screen.queryByRole('link', { name: 'Units' })).toBeNull();

    const openLink = screen.getByRole('link', { name: 'Open' });
    const pendingLink = screen.getByRole('link', { name: 'Pending' });

    expect(openLink.className).toContain(activeRowClass);
    expect(pendingLink.className).not.toContain(activeRowClass);
  });
});
