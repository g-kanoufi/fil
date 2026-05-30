function normalizePathname(pathname: string): string {
  const base = pathname.split('?')[0]?.trim() || '/';

  if (base === '/') {
    return '/';
  }

  return base.replace(/\/+$/, '') || '/';
}

function parseSearch(search: string): URLSearchParams {
  const raw = search.startsWith('?') ? search.slice(1) : search;

  return new URLSearchParams(raw);
}

/** Match legacy fl-react sidebar query normalization. */
export function normalizeQueryValue(value: string): string {
  return (value || '').replace(/[-\s]+/g, '_').toLowerCase();
}

function queryValueMatches(key: string, locationValue: string, itemValue: string): boolean {
  if (key === 'filter' || key === 'subFilter') {
    return normalizeQueryValue(locationValue) === normalizeQueryValue(itemValue);
  }

  return locationValue === itemValue;
}

/** True when the location pathname matches the nav item (exact or nested detail route). */
export function pathnameMatches(locationPath: string, itemPath: string): boolean {
  const location = normalizePathname(locationPath);
  const item = normalizePathname(itemPath);

  if (item === '/') {
    return location === '/';
  }

  if (location === item) {
    return true;
  }

  return location.startsWith(`${item}/`);
}

/** True when pathname and query params match this nav item exactly. */
export function navItemMatches(locationPath: string, locationSearch: string, itemPath: string): boolean {
  const [itemPathname, itemQuery = ''] = itemPath.split('?');

  if (!pathnameMatches(locationPath, itemPathname)) {
    return false;
  }

  const locationParams = parseSearch(locationSearch);
  const itemParams = parseSearch(itemQuery);
  const isReportRoute =
    normalizePathname(itemPathname).startsWith('/reports/') ||
    normalizePathname(locationPath).startsWith('/reports/');

  if (isReportRoute) {
    const locationFilter = normalizeQueryValue(locationParams.get('filter') ?? '');
    const locationSubFilter = normalizeQueryValue(locationParams.get('subFilter') ?? '');
    const itemFilter = normalizeQueryValue(itemParams.get('filter') ?? '');
    const itemSubFilter = normalizeQueryValue(itemParams.get('subFilter') ?? '');

    if (!itemQuery) {
      return locationFilter === '' && locationSubFilter === '';
    }

    return locationFilter === itemFilter && locationSubFilter === itemSubFilter;
  }

  if (!itemQuery) {
    return parseSearch(locationSearch).toString() === '';
  }

  for (const [key, value] of itemParams.entries()) {
    const locationValue = locationParams.get(key) ?? '';

    if (!queryValueMatches(key, locationValue, value)) {
      return false;
    }
  }

  return true;
}

export function navItemHasActiveDescendant(
  locationPath: string,
  locationSearch: string,
  children: Array<{ path: string; children?: Array<{ path: string; children?: unknown[] }> }> = [],
): boolean {
  return children.some((child) => {
    if (navItemMatches(locationPath, locationSearch, child.path)) {
      return true;
    }

    return navItemHasActiveDescendant(locationPath, locationSearch, child.children ?? []);
  });
}
