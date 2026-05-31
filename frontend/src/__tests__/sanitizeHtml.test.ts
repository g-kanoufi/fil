import { describe, expect, it } from 'vitest';
import { sanitizeHtml } from '@/lib/security/sanitizeHtml';

describe('sanitizeHtml', () => {
  it('preserves safe inline markup', () => {
    expect(sanitizeHtml('<b>hi</b> <em>there</em>')).toBe('<b>hi</b> <em>there</em>');
  });

  it('strips script tags', () => {
    const result = sanitizeHtml('<p>ok</p><script>alert(1)</script>');
    expect(result).not.toContain('<script');
  });

  it('removes inline event handlers', () => {
    expect(sanitizeHtml('<a onclick="steal()">y</a>')).toBe('<a>y</a>');
  });

  it('strips javascript: urls in links', () => {
    expect(sanitizeHtml('<a href="javascript:alert(1)">x</a>')).not.toContain('javascript:');
  });

  it('strips dangerous container tags', () => {
    const result = sanitizeHtml('<iframe src="x"></iframe><object></object><b>keep</b>');
    expect(result).toBe('<b>keep</b>');
  });
});
