import DOMPurify from 'dompurify';

/**
 * Sanitize untrusted rich-text HTML before rendering via dangerouslySetInnerHTML.
 * Uses DOMPurify (vetted allowlist) which removes scripts, event handlers, and
 * dangerous URLs (e.g. `javascript:` in href/src) that the previous regex missed.
 */
export function sanitizeHtml(html: string): string {
  return DOMPurify.sanitize(html, { USE_PROFILES: { html: true } }).trim();
}
