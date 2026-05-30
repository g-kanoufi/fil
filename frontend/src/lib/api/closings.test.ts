import { describe, expect, it } from 'vitest';
import { centsToDollarInput, formatFeeCents, parseDollarsToCents } from '@/lib/api/closings';

describe('closings api helpers', () => {
  it('formats cents as USD', () => {
    expect(formatFeeCents(125000)).toContain('1,250');
  });

  it('parses dollar strings to cents', () => {
    expect(parseDollarsToCents('$4,500.50')).toBe(450050);
    expect(centsToDollarInput(450050)).toBe('4500.50');
  });
});
