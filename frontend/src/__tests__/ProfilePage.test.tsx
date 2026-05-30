import { render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi, beforeEach } from 'vitest';
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
    email: 'admin@fil.test',
    name: 'FIL Admin',
    first_name: 'FIL',
    last_name: 'Admin',
    phone: null,
    roles: ['admin'],
    primary_role: 'admin',
    permissions: ['app.access', 'leads.view', 'stores.view'],
    navigation: [
      { id: 'dashboard', label: 'Dashboard', path: '/' },
      { id: 'profile', label: 'My profile', path: '/profile' },
    ],
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
    authorized_notes: { application: { notes: true, private_notes: true } },
    hidden_field_keys: [],
    readonly_field_keys: [],
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
    leads: { total: 1, by_status: [], added_monthly: [] },
    stores: { total: 1 },
    fdd_deliveries: { sent_30d: 0, total: 0, sent_monthly: [] },
    recent_leads: [],
    pipeline: [],
  }),
}));

vi.mock('@/lib/api/profile', () => ({
  fetchProfile: vi.fn().mockResolvedValue({
    id: 1,
    email: 'admin@fil.test',
    first_name: 'FIL',
    last_name: 'Admin',
    name: 'FIL Admin',
    phone: null,
    roles: ['admin'],
    primary_role: 'admin',
    created_at: null,
    updated_at: null,
  }),
  updateProfile: vi.fn(),
}));

describe('ProfilePage route', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders profile form for authenticated staff', async () => {
    render(
      <ThemeProvider>
        <ClientBrandingProvider>
          <AuthProvider>
            <MemoryRouter initialEntries={['/profile']}>
              <AppRoutes />
            </MemoryRouter>
          </AuthProvider>
        </ClientBrandingProvider>
      </ThemeProvider>,
    );

    expect(await screen.findByRole('heading', { name: 'My profile', level: 1 })).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByDisplayValue('admin@fil.test')).toBeInTheDocument();
    });
  });
});
