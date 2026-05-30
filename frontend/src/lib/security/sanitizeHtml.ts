const BLOCKED_TAGS = /<\/?(?:script|style|iframe|object|embed|link|meta)\b[^>]*>/gi;
const EVENT_HANDLERS = /\son\w+\s*=\s*(".*?"|'.*?'|[^\s>]+)/gi;
const JS_URLS = /\s(href|src)\s*=\s*(".*?"|'.*?')\s*javascript:[^"']*/gi;

export function sanitizeHtml(html: string): string {
  return html
    .replace(BLOCKED_TAGS, '')
    .replace(EVENT_HANDLERS, '')
    .replace(JS_URLS, '')
    .trim();
}
