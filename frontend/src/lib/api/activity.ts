import { downloadBlob } from '@/lib/export/csv';
import { apiGet, apiGetBlob } from './client';

export interface ActivitySubject {
  type: string;
  id: number;
  label: string;
  path: string | null;
}

export interface ActivityItem {
  id: string;
  occurred_at: string | null;
  actor: { id: number | null; name: string };
  category: string;
  action: string;
  summary: string;
  subject: ActivitySubject | null;
  source: string;
}

export interface ActivityFeedResponse {
  data: ActivityItem[];
  meta: {
    next_cursor: string | null;
    has_more: boolean;
  };
}

export function fetchActivityFeed(params?: {
  limit?: number;
  cursor?: string;
  days?: number;
  category?: string;
}): Promise<ActivityFeedResponse> {
  const search = new URLSearchParams();

  if (params?.limit) {
    search.set('limit', String(params.limit));
  }

  if (params?.cursor) {
    search.set('cursor', params.cursor);
  }

  if (params?.days) {
    search.set('days', String(params.days));
  }

  if (params?.category) {
    search.set('category', params.category);
  }

  const query = search.toString();

  return apiGet<ActivityFeedResponse>(`/v1/activity${query ? `?${query}` : ''}`);
}

export async function fetchAllActivityFeed(days: number): Promise<ActivityItem[]> {
  const items: ActivityItem[] = [];
  let cursor: string | undefined;

  do {
    const response = await fetchActivityFeed({
      days,
      limit: 200,
      cursor,
    });

    items.push(...response.data);
    cursor = response.meta.next_cursor ?? undefined;
  } while (cursor);

  return items;
}

export async function downloadActivityExport(
  days: number,
  filename: string,
  category?: string,
): Promise<void> {
  const search = new URLSearchParams({ days: String(days) });

  if (category) {
    search.set('category', category);
  }

  const blob = await apiGetBlob(`/v1/activity/export?${search.toString()}`, {
    headers: { Accept: 'text/csv' },
  });

  downloadBlob(blob, filename);
}

export function fetchSubjectActivity(
  type: string,
  id: number,
  limit = 25,
): Promise<{ data: ActivityItem[] }> {
  return apiGet<{ data: ActivityItem[] }>(`/v1/activity/subjects/${type}/${id}?limit=${limit}`);
}
