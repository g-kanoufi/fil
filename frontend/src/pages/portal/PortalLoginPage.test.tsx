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

vi.mock('@/providers/ClientBrandingProvider', () => ({
  useClientBrand: () => ({ brandName: 'FIL', logoUrl: null }),
}));

describe('PortalLoginPage', () => {
  it('renders shared sign-in shell with brand and prospect heading', () => {
    render(
      <MemoryRouter>
        <PortalLoginPage />
      </MemoryRouter>,
    );

    expect(screen.getByRole('heading', { name: 'Prospect sign in' })).toBeInTheDocument();
    expect(screen.getByText('FIL')).toBeInTheDocument();
    expect(screen.getByLabelText('Email')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Sign in' })).toBeInTheDocument();
  });
});
