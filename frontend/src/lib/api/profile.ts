import { apiGet, apiPatch } from './client';

export interface UserProfile {
  id: number;
  email: string;
  first_name: string | null;
  last_name: string | null;
  name: string;
  phone: string | null;
  roles: string[];
  primary_role: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface UpdateProfilePayload {
  first_name?: string;
  last_name?: string;
  email: string;
  phone?: string;
  current_password?: string;
  password?: string;
  password_confirmation?: string;
}

export function fetchProfile(): Promise<UserProfile> {
  return apiGet<{ data: UserProfile }>('/v1/profile').then((body) => body.data);
}

export function updateProfile(payload: UpdateProfilePayload): Promise<UserProfile> {
  return apiPatch<{ data: UserProfile }>('/v1/profile', payload).then((body) => body.data);
}
