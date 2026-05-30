import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { LoginPage } from '@/pages/LoginPage';
import { ClientBrandingProvider } from '@/providers/ClientBrandingProvider';

vi.mock('@/lib/api/branding', () => ({
  fetchPublicBranding: vi.fn().mockResolvedValue({ brandName: 'FIL' }),
}));

const loginMock = vi.fn();

vi.mock('@/providers/AuthProvider', () => ({
  useAuth: () => ({
    status: 'unauthenticated' as const,
    user: null,
    appConfig: null,
    login: loginMock,
    logout: vi.fn(),
    refresh: vi.fn(),
    can: () => false,
    hasRole: () => false,
    isUiDisabled: () => true,
  }),
}));

describe('LoginPage', () => {
  it('submits email and password via auth login', async () => {
    loginMock.mockResolvedValue(undefined);

    render(
      <MemoryRouter>
        <ClientBrandingProvider>
          <LoginPage />
        </ClientBrandingProvider>
      </MemoryRouter>,
    );

    fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'admin@fil.test' } });
    fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'password' } });
    fireEvent.click(screen.getByRole('button', { name: 'Sign in' }));

    await waitFor(() => {
      expect(loginMock).toHaveBeenCalledWith('admin@fil.test', 'password');
    });
  });

  it('shows validation error when login fails', async () => {
    const { ApiError } = await import('@/lib/api/client');
    loginMock.mockRejectedValue(
      new ApiError(422, 'Login failed.', {
        errors: { email: ['The provided credentials are incorrect.'] },
      }),
    );

    render(
      <MemoryRouter>
        <ClientBrandingProvider>
          <LoginPage />
        </ClientBrandingProvider>
      </MemoryRouter>,
    );

    fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'admin@fil.test' } });
    fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'wrong' } });
    fireEvent.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(await screen.findByText('The provided credentials are incorrect.')).toBeInTheDocument();
  });
});
