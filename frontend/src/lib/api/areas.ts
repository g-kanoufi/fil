import { apiGet } from './client';

export interface Area {
  id: number;
  name: string;
  slug: string | null;
  status: string;
  approval_status: string | null;
}

export function fetchAreas(): Promise<Area[]> {
  return apiGet<{ data: Area[] }>('/v1/areas').then((body) => body.data);
}
