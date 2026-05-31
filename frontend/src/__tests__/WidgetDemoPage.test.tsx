import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/providers/AuthProvider', () => ({
  useAuth: () => ({
    can: () => true,
    user: { permissions: ['fields.manage'] },
  }),
}));

vi.mock('@/lib/api/widgetForms', () => ({
  fetchWidgetForms: vi.fn().mockResolvedValue([
    { id: 1, key: 'lead_short', name: 'Short', site_key: 'pk_dev', status: 'active', fields: [] },
  ]),
}));

import { WidgetDemoPage } from '@/pages/WidgetDemoPage';

describe('WidgetDemoPage', () => {
  it('shows embed instructions and preview frames', async () => {
    render(
      <MemoryRouter>
        <WidgetDemoPage />
      </MemoryRouter>,
    );

    expect(await screen.findByText(/How to embed on a client site/i)).toBeInTheDocument();
    expect(screen.getByText(/FIL_EMBED_ALLOWED_ORIGINS/i)).toBeInTheDocument();
    expect(screen.getByTitle('FIL widget inline preview')).toHaveAttribute('src', '/embed-demo/inline');
    expect(screen.getByTitle('FIL widget iframe preview')).toHaveAttribute('src', '/embed-demo/frame');
  });
});
