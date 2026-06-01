import { beforeEach, describe, expect, it, vi } from 'vitest';
import { downloadClosingsExport } from '@/lib/api/closings';
import { apiGetBlob } from '@/lib/api/client';
import { downloadBlob } from '@/lib/export/csv';

vi.mock('@/lib/api/client', () => ({
  apiGetBlob: vi.fn(),
}));

vi.mock('@/lib/export/csv', () => ({
  downloadBlob: vi.fn(),
}));

describe('downloadClosingsExport', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('requests the server CSV endpoint and downloads the blob', async () => {
    const blob = new Blob(['closing_id,title\n'], { type: 'text/csv' });
    vi.mocked(apiGetBlob).mockResolvedValue(blob);

    await downloadClosingsExport('fil-closings-2026-06-01.csv');

    expect(apiGetBlob).toHaveBeenCalledWith('/v1/closings/export', {
      headers: { Accept: 'text/csv' },
    });
    expect(downloadBlob).toHaveBeenCalledWith(blob, 'fil-closings-2026-06-01.csv');
  });
});
