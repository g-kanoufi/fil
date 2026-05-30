import { describe, expect, it } from 'vitest';
import { ApiError } from '@/lib/api/client';
import { resolveApiAuthFailure } from '@/lib/api/authBridge';

describe('resolveApiAuthFailure', () => {
  it('maps 401 to login', () => {
    expect(resolveApiAuthFailure(new ApiError(401, 'nope'))).toBe('login');
  });

  it('maps staff_required 403 to login', () => {
    expect(resolveApiAuthFailure(new ApiError(403, 'nope', { code: 'staff_required' }))).toBe('login');
  });

  it('maps policy 403 to forbidden', () => {
    expect(resolveApiAuthFailure(new ApiError(403, 'nope', { code: 'forbidden' }))).toBe('forbidden');
  });

  it('maps unknown 403 to forbidden', () => {
    expect(resolveApiAuthFailure(new ApiError(403, 'nope'))).toBe('forbidden');
  });
});
