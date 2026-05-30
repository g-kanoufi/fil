import { apiDelete, apiGet, apiPost } from '@/lib/api/client';
import type { SessionResponse, SessionUser } from '@/types/auth';

export function fetchSession(): Promise<SessionUser> {
  return apiGet<SessionResponse>('/v1/session').then((body) => body.data);
}

export async function login(email: string, password: string): Promise<SessionUser> {
  await ensureCsrfCookie();

  return apiPost<SessionResponse>('/v1/session', { email, password }).then((body) => body.data);
}

export function logout(): Promise<void> {
  return apiDelete<void>('/v1/session').then(() => undefined);
}

async function ensureCsrfCookie(): Promise<void> {
  if (typeof window === 'undefined') {
    return;
  }

  await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
}
