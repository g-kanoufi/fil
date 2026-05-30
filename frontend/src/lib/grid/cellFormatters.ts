export function formatGridDate(value: unknown): string {
  if (!value) {
    return '—';
  }

  const date = new Date(String(value));
  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return date.toLocaleDateString();
}

export function formatGridLink(label: unknown, href: string): string {
  if (!label) {
    return '—';
  }

  return `<a href="${href}" class="text-link font-medium hover:underline">${String(label)}</a>`;
}
