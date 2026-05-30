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
          key: 'referral_notes',
          name: 'Referral Notes',
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
  fetchRelatableEntities: vi.fn().mockResolvedValue({
    types: ['text', 'select', 'relation_one', 'relation_many'],
    relatable_entities: [{ key: 'area', label: 'Areas' }],
  }),
  createField: vi.fn(),
  deleteField: vi.fn(),
  reorderFields: vi.fn(),
}));

import { FieldsAdminPage } from '@/pages/FieldsAdminPage';

describe('FieldsAdminPage', () => {
  it('renders groups and their fields', async () => {
    render(
      <MemoryRouter>
        <FieldsAdminPage />
      </MemoryRouter>,
    );

    expect(screen.getByRole('heading', { name: 'Custom fields' })).toBeInTheDocument();
    expect(await screen.findByText('Referral Notes')).toBeInTheDocument();
    expect(screen.getByText(/referral_notes/)).toBeInTheDocument();
  });
});
