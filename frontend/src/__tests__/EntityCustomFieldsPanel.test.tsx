import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { EntityCustomFieldsPanel } from '@/components/fields/EntityCustomFieldsPanel';
import { fetchFieldSchema } from '@/lib/api/fields';

vi.mock('@/lib/api/fields', () => ({
  fetchFieldSchema: vi.fn(),
}));

vi.mock('@/providers/AuthProvider', () => ({
  useAuth: () => ({
    isFieldHidden: () => false,
    isFieldReadonly: () => false,
  }),
}));

describe('EntityCustomFieldsPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('loads editable scalar fields and saves changes', async () => {
    vi.mocked(fetchFieldSchema).mockResolvedValue([
      {
        id: 1,
        key: 'applications',
        title: 'Applications',
        fields: [
          {
            id: 1,
            key: 'referral_notes',
            name: 'Referral notes',
            type: 'textarea',
            entity: 'lead',
            storage: 'field_value',
            maps_to_column: null,
            status: 'active',
            sort_order: 1,
            required: false,
            is_filterable: false,
            is_sortable: false,
            is_facetable: false,
            field_group_id: 1,
            config: {},
          },
        ],
      },
    ]);

    const onSave = vi.fn().mockResolvedValue(undefined);

    render(
      <EntityCustomFieldsPanel
        entity="lead"
        values={{ referral_notes: 'Initial note' }}
        canEdit
        onSave={onSave}
      />,
    );

    await waitFor(() => {
      expect(screen.getByLabelText(/referral notes/i)).toBeInTheDocument();
    });

    const textarea = screen.getByLabelText(/referral notes/i);
    fireEvent.change(textarea, { target: { value: 'Updated note' } });
    fireEvent.click(screen.getByRole('button', { name: /save custom fields/i }));

    await waitFor(() => {
      expect(onSave).toHaveBeenCalledWith({ referral_notes: 'Updated note' });
    });
  });

  it('shows empty state when no editable fields exist', async () => {
    vi.mocked(fetchFieldSchema).mockResolvedValue([]);

    render(
      <EntityCustomFieldsPanel
        entity="lead"
        values={{}}
        canEdit={false}
        onSave={vi.fn()}
      />,
    );

    await waitFor(() => {
      expect(screen.getByText(/no custom fields/i)).toBeInTheDocument();
    });
  });
});
