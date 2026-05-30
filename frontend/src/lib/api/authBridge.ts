import type { ApiError } from '@/lib/api/client';

export type ApiAuthFailure = 'login' | 'forbidden';

export type ApiAuthErrorHandler = (error: ApiError) => ApiAuthFailure | void;

let handler: ApiAuthErrorHandler | null = null;

export function setApiAuthErrorHandler(next: ApiAuthErrorHandler | null): void {
  handler = next;
}

export function resolveApiAuthFailure(error: ApiError): ApiAuthFailure | null {
  if (error.status === 401) {
    return 'login';
  }

  const code = readErrorCode(error.body);

  if (error.status === 403 && (code === 'staff_required' || code === 'unauthenticated')) {
    return 'login';
  }

  if (error.status === 403) {
    return 'forbidden';
  }

  return null;
}

export function notifyApiAuthError(error: ApiError): ApiAuthFailure | null {
  const resolved = resolveApiAuthFailure(error);

  if (resolved && handler) {
    handler(error);
  }

  return resolved;
}

function readErrorCode(body: unknown): string | null {
  if (!body || typeof body !== 'object' || !('code' in body)) {
    return null;
  }

  const code = (body as { code?: unknown }).code;

  return typeof code === 'string' ? code : null;
}
