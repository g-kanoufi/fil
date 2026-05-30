function slugSegment(value: string | undefined): string | null {
  if (!value?.trim()) {
    return null;
  }

  const slug = value
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

  return slug || null;
}

function exportDateStamp(date = new Date()): string {
  return date.toISOString().slice(0, 10);
}

/** `fil-{parts...}-{yyyy-mm-dd}.csv` */
export function buildExportFilename(parts: string[], date = new Date()): string {
  const segments = ['fil', ...parts.filter(Boolean), exportDateStamp(date)];

  return `${segments.join('-')}.csv`;
}

export function gridExportFilename(
  resource: string,
  filter?: string,
  subFilter?: string,
  date = new Date(),
): string {
  const parts = [resource, slugSegment(filter), slugSegment(subFilter)].filter(
    (part): part is string => part !== null,
  );

  return buildExportFilename(parts, date);
}

export function activityFeedExportFilename(days: number, date = new Date()): string {
  return buildExportFilename([`activity-${days}d`], date);
}

export function subjectActivityExportFilename(
  subjectType: string,
  subjectId: number,
  date = new Date(),
): string {
  return buildExportFilename([subjectType, String(subjectId), 'activity'], date);
}
