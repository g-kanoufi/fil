import { apiDelete, apiGet, apiPatch, apiPost } from './client';

export interface InterestRegion {
  id: number;
  parent_id: number | null;
  name: string;
  code: string | null;
  slug: string;
  sort_order: number;
  status: 'active' | 'inactive';
  legacy_term_id: number | null;
  label: string;
  children?: InterestRegion[];
}

export function syncInterestRegionDefaults(): Promise<{ countries: number; subdivisions: number }> {
  return apiPost<{ data: { countries: number; subdivisions: number; message?: string } }>(
    '/v1/interest-regions/sync-defaults',
    {},
  ).then((body) => ({
    countries: body.data.countries,
    subdivisions: body.data.subdivisions,
  }));
}

export function fetchInterestRegions(options?: { flat?: boolean }): Promise<InterestRegion[]> {
  const query = options?.flat ? '?flat=1' : '';

  return apiGet<{ data: InterestRegion[] }>(`/v1/interest-regions${query}`).then((body) => body.data);
}

export function createInterestRegion(payload: {
  parent_id?: number | null;
  name: string;
  code?: string | null;
  slug?: string;
  sort_order?: number;
  status?: InterestRegion['status'];
  legacy_term_id?: number | null;
}): Promise<InterestRegion> {
  return apiPost<{ data: InterestRegion }>('/v1/interest-regions', payload).then((body) => body.data);
}

export function updateInterestRegion(
  id: number,
  payload: Partial<{
    parent_id: number | null;
    name: string;
    code: string | null;
    slug: string;
    sort_order: number;
    status: InterestRegion['status'];
    legacy_term_id: number | null;
  }>,
): Promise<InterestRegion> {
  return apiPatch<{ data: InterestRegion }>(`/v1/interest-regions/${id}`, payload).then((body) => body.data);
}

export function deleteInterestRegion(id: number): Promise<void> {
  return apiDelete(`/v1/interest-regions/${id}`).then(() => undefined);
}
