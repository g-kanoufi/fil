import { beforeEach, describe, expect, it, vi } from 'vitest';
import { downloadActivityExport } from '@/lib/api/activity';
import { apiGetBlob } from '@/lib/api/client';
import { downloadBlob } from '@/lib/export/csv';

vi.mock('@/lib/api/client', () => ({
  apiGetBlob: vi.fn(),
}));

vi.mock('@/lib/export/csv', () => ({
  downloadBlob: vi.fn(),
}));

describe('downloadActivityExport', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('requests the server CSV endpoint and downloads the blob', async () => {
    const blob = new Blob(['occurred_at,category\n'], { type: 'text/csv' });
    vi.mocked(apiGetBlob).mockResolvedValue(blob);

    await downloadActivityExport(30, 'fil-activity-30d-2026-05-31.csv');

    expect(apiGetBlob).toHaveBeenCalledWith(
      '/v1/activity/export?days=30',
      { headers: { Accept: 'text/csv' } },
    );
    expect(downloadBlob).toHaveBeenCalledWith(blob, 'fil-activity-30d-2026-05-31.csv');
  });

  it('includes the category filter when provided', async () => {
    vi.mocked(apiGetBlob).mockResolvedValue(new Blob([''], { type: 'text/csv' }));

    await downloadActivityExport(7, 'export.csv', 'auth');

    expect(apiGetBlob).toHaveBeenCalledWith(
      '/v1/activity/export?days=7&category=auth',
      { headers: { Accept: 'text/csv' } },
    );
  });
});
