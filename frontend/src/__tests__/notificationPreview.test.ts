import { describe, expect, it } from 'vitest';
import { sanitizeHtml } from '@/lib/security/sanitizeHtml';

describe('notification body preview sanitization', () => {
  it('keeps safe markup used in notification templates', () => {
    const html = '<p>Hello <strong>team</strong></p><ul><li>One</li></ul>';
    expect(sanitizeHtml(html)).toContain('<strong>team</strong>');
    expect(sanitizeHtml(html)).toContain('<li>One</li>');
  });

  it('strips script tags from notification preview html', () => {
    const html = '<p>Welcome</p><script>alert("xss")</script>';
    const sanitized = sanitizeHtml(html);

    expect(sanitized).toContain('Welcome');
    expect(sanitized).not.toContain('<script');
    expect(sanitized).not.toContain('alert');
  });

  it('removes inline event handlers from preview links', () => {
    expect(sanitizeHtml('<a onclick="steal()" href="https://example.com">Go</a>')).not.toContain('onclick');
  });
});
