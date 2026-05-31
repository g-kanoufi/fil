import { notifyApiAuthError } from '@/lib/api/authBridge';

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly body?: unknown,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  code(): string | null {
    if (!this.body || typeof this.body !== 'object' || !('code' in this.body)) {
      return null;
    }

    const value = (this.body as { code?: unknown }).code;

    return typeof value === 'string' ? value : null;
  }
}

const baseUrl = import.meta.env.VITE_API_URL ?? '/api';

function readXsrfToken(): string | null {
  if (typeof document === 'undefined') {
    return null;
  }

  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

  return match ? decodeURIComponent(match[1]) : null;
}

function withMutationHeaders(headers?: HeadersInit): HeadersInit {
  const xsrf = readXsrfToken();

  if (!xsrf) {
    return headers ?? {};
  }

  return {
    ...headers,
    'X-XSRF-TOKEN': xsrf,
  };
}

async function parseJson(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text) {
    return null;
  }

  try {
    return JSON.parse(text) as unknown;
  } catch {
    return text;
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${baseUrl}${path}`, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...init.headers,
    },
    ...init,
  });

  const body = await parseJson(response);

  if (!response.ok) {
    const error = new ApiError(
      response.status,
      `API ${init.method ?? 'GET'} ${path} failed: ${response.status}`,
      body,
    );

    notifyApiAuthError(error);

    throw error;
  }

  return body as T;
}

export function apiGet<T>(path: string, init?: RequestInit): Promise<T> {
  return request<T>(path, { ...init, method: 'GET' });
}

export async function apiGetBlob(path: string, init?: RequestInit): Promise<Blob> {
  const response = await fetch(`${baseUrl}${path}`, {
    credentials: 'include',
    ...init,
    method: 'GET',
    headers: { ...init?.headers },
  });

  if (!response.ok) {
    const error = new ApiError(
      response.status,
      `API GET ${path} failed: ${response.status}`,
      await parseJson(response),
    );

    notifyApiAuthError(error);

    throw error;
  }

  return response.blob();
}

export function apiPost<T>(path: string, payload?: unknown, init?: RequestInit): Promise<T> {
  return request<T>(path, {
    ...init,
    method: 'POST',
    headers: withMutationHeaders({
      'Content-Type': 'application/json',
      ...init?.headers,
    }),
    body: payload === undefined ? undefined : JSON.stringify(payload),
  });
}

export function apiPatch<T>(path: string, payload?: unknown, init?: RequestInit): Promise<T> {
  return request<T>(path, {
    ...init,
    method: 'PATCH',
    headers: withMutationHeaders({
      'Content-Type': 'application/json',
      ...init?.headers,
    }),
    body: payload === undefined ? undefined : JSON.stringify(payload),
  });
}

export function apiPut<T>(path: string, payload?: unknown, init?: RequestInit): Promise<T> {
  return request<T>(path, {
    ...init,
    method: 'PUT',
    headers: withMutationHeaders({
      'Content-Type': 'application/json',
      ...init?.headers,
    }),
    body: payload === undefined ? undefined : JSON.stringify(payload),
  });
}

export function apiDelete<T>(path: string, init?: RequestInit): Promise<T> {
  return request<T>(path, {
    ...init,
    method: 'DELETE',
    headers: withMutationHeaders(init?.headers),
  });
}

export function apiPostForm<T>(path: string, formData: FormData, init?: RequestInit): Promise<T> {
  return request<T>(path, {
    ...init,
    method: 'POST',
    headers: withMutationHeaders(init?.headers),
    body: formData,
  });
}

export function apiPatchForm<T>(path: string, formData: FormData, init?: RequestInit): Promise<T> {
  return request<T>(path, {
    ...init,
    method: 'PATCH',
    headers: withMutationHeaders(init?.headers),
    body: formData,
  });
}
