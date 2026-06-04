import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { PortalLoginPage } from '@/pages/portal/PortalLoginPage';

vi.mock('@/providers/PortalAuthProvider', () => ({
  usePortalAuth: () => ({
    status: 'unauthenticated',
    login: vi.fn(),
  }),
}));

describe('PortalLoginPage', () => {
  it('renders prospect sign in heading', () => {
    render(
      <MemoryRouter>
        <PortalLoginPage />
      </MemoryRouter>,
    );

    expect(screen.getByRole('heading', { name: 'Prospect sign in' })).toBeInTheDocument();
  });
});
