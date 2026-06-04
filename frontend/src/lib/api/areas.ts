import { apiDelete, apiGet, apiPatch, apiPost } from './client';

export interface AreaTerritory {
  country?: string;
  subdivisions?: string[];
}

export interface Area {
  id: number;
  name: string;
  slug: string | null;
  status: string;
  approval_status: string | null;
  territory?: AreaTerritory | null;
  store_count?: number;
}

export interface NorthAmericaCountry {
  code: string;
  name: string;
  subdivisions: Array<{ code: string; name: string }>;
}

export function fetchAreas(): Promise<Area[]> {
  return apiGet<{ data: Area[] }>('/v1/areas').then((body) => body.data);
}

export function createArea(payload: {
  name: string;
  slug?: string;
  status?: string;
  approval_status?: string | null;
  territory?: AreaTerritory | null;
}): Promise<Area> {
  return apiPost<{ data: Area }>('/v1/areas', payload).then((body) => body.data);
}

export function updateArea(
  id: number,
  payload: Partial<{
    name: string;
    slug: string | null;
    status: string;
    approval_status: string | null;
    territory: AreaTerritory | null;
  }>,
): Promise<Area> {
  return apiPatch<{ data: Area }>(`/v1/areas/${id}`, payload).then((body) => body.data);
}

export function deleteArea(id: number): Promise<void> {
  return apiDelete(`/v1/areas/${id}`).then(() => undefined);
}

export function fetchNorthAmericaGeography(): Promise<NorthAmericaCountry[]> {
  return apiGet<{ data: { countries: NorthAmericaCountry[] } }>('/v1/geography/north-america').then(
    (body) => body.data.countries,
  );
}
