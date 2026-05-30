import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { AppRoutes } from '@/App';
import { AuthProvider } from '@/providers/AuthProvider';
import { ClientBrandingProvider } from '@/providers/ClientBrandingProvider';
import { ThemeProvider } from '@/providers/ThemeProvider';

vi.mock('@/lib/api/branding', () => ({
  fetchPublicBranding: vi.fn().mockResolvedValue({ brandName: 'FIL' }),
}));

vi.mock('@/lib/api/session', () => ({
  fetchSession: vi.fn().mockResolvedValue({
    id: 1,
    email: 'staff@fil.test',
    name: 'Staff User',
    first_name: 'Staff',
    last_name: 'User',
    roles: ['lead_owner'],
    primary_role: 'lead_owner',
    permissions: ['app.access', 'leads.view'],
    navigation: [{ id: 'dashboard', label: 'Dashboard', path: '/' }],
    ui_restrictions: {
      tabs: [],
      leads: { menuItems: [], subMenuItems: [] },
      stores: { menuItems: [], subMenuItems: [] },
      contacts: { menuItems: [], subMenuItems: [] },
      nav_admin: { menuItems: [], subMenuItems: [] },
      nav_stores: { menuItems: [], subMenuItems: [] },
      nav_contacts: { menuItems: [], subMenuItems: [] },
      resources: [],
      allowed_zai_resources: [],
      features: { detail_panel: true, zai_assistant: true },
    },
    authorized_notes: { application: { notes: true, private_notes: false } },
    user_has_panel_access: true,
  }),
  login: vi.fn(),
  logout: vi.fn(),
}));

vi.mock('@/lib/api/app-config', () => ({
  fetchAppConfig: vi.fn().mockResolvedValue({
    options: { brandName: 'FIL' },
    menus: { top_menus: [], menus_with_columns: {} },
  }),
}));

vi.mock('@/lib/api/health', () => ({
  fetchHealth: vi.fn().mockResolvedValue({ status: 'ok', service: 'fil-api' }),
}));

vi.mock('@/lib/api/dashboard', () => ({
  fetchDashboardStats: vi.fn().mockResolvedValue({
    leads: {
      total: 12,
      by_status: [{ status: 'active', count: 12 }],
      added_monthly: [{ month: '2026-05', label: 'May', count: 4 }],
    },
    stores: { total: 3 },
    fdd_deliveries: {
      sent_30d: 2,
      total: 5,
      sent_monthly: [{ month: '2026-05', label: 'May', count: 1 }],
    },
    recent_leads: [],
    pipeline: [{ phase: 'Intake', label: 'Intake', count: 8 }],
  }),
}));

describe('App', () => {
  it('renders staff home when session is valid', async () => {
    render(
      <ThemeProvider>
        <ClientBrandingProvider>
          <MemoryRouter initialEntries={['/']}>
            <AuthProvider>
              <AppRoutes />
            </AuthProvider>
          </MemoryRouter>
        </ClientBrandingProvider>
      </ThemeProvider>,
    );

    expect(await screen.findByText(/Signed in as Staff User/)).toBeInTheDocument();
    expect(screen.getByRole('navigation', { name: 'Staff navigation' })).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Dashboard', level: 1 })).toBeInTheDocument();
  });
});
