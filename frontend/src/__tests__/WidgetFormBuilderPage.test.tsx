import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/providers/AuthProvider', () => ({
  useAuth: () => ({ can: () => true }),
}));

vi.mock('@/lib/api/fields', () => ({
  fetchFieldGroups: vi.fn().mockResolvedValue([
    {
      id: 1,
      key: 'applications',
      title: 'Applications',
      slug: 'applications',
      sort_order: 1,
      status: 'active',
      fields: [
        {
          id: 10,
          key: 'preferred_market',
          name: 'Preferred Market',
          type: 'text',
          entity: 'lead',
          storage: 'field_value',
          maps_to_column: null,
          config: {},
          required: false,
          is_filterable: false,
          is_sortable: false,
          is_facetable: false,
          sort_order: 1,
          status: 'active',
          field_group_id: 1,
        },
      ],
    },
  ]),
}));

vi.mock('@/lib/api/widgetForms', () => ({
  fetchWidgetForms: vi.fn().mockResolvedValue([
    { id: 5, key: 'lead_short', name: 'Short', site_key: 'pk_dev', entity: 'lead', version: 1, status: 'active', settings: {}, fields: [] },
  ]),
  fetchWidgetForm: vi.fn().mockResolvedValue({
    id: 5,
    key: 'lead_short',
    name: 'Short',
    site_key: 'pk_dev',
    entity: 'lead',
    version: 1,
    status: 'active',
    settings: {},
    fields: [],
  }),
  createWidgetForm: vi.fn(),
  updateWidgetForm: vi.fn(),
  syncWidgetFormFields: vi.fn(),
}));

import { WidgetFormBuilderPage } from '@/pages/WidgetFormBuilderPage';

describe('WidgetFormBuilderPage', () => {
  it('renders the palette of available lead fields', async () => {
    render(
      <MemoryRouter>
        <WidgetFormBuilderPage />
      </MemoryRouter>,
    );

    expect(screen.getByRole('heading', { name: 'Widget form builder' })).toBeInTheDocument();
    expect(await screen.findByText('Preferred Market')).toBeInTheDocument();
    expect(screen.getByText('Available fields')).toBeInTheDocument();
  });
});
