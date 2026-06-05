import type { AppConfig, NavItem, NavSection } from '@/types/auth';
import { menusForResource } from '@/lib/grid/menus';

const REPORT_RESOURCES = ['stores', 'contacts', 'leads'] as const;

type ReportResource = (typeof REPORT_RESOURCES)[number];

/** Submenu values rendered as siblings under the report root (no parent row link). */
export const FLAT_SUBMENU_GROUPS: Record<ReportResource, readonly string[]> = {
  leads: ['lead_status'],
  stores: ['store_status'],
  contacts: [],
};

/** Grid-only filters — not shown in the sidebar (dedicated pages instead). */
export const SIDEBAR_EXCLUDED_MENU_KEYS: Record<ReportResource, readonly string[]> = {
  leads: [],
  stores: ['store_area'],
  contacts: [],
};

/** Grid filter chips flatten these groups; sidebar uses nested children instead. */
export const GRID_FLAT_SUBMENU_GROUPS: Record<ReportResource, readonly string[]> = {
  leads: ['lead_status'],
  stores: ['store_status', 'store_area'],
  contacts: ['contact_role'],
};

function isReportResource(id: string): id is ReportResource {
  return REPORT_RESOURCES.includes(id as ReportResource);
}

function subFilterSlug(subKey: string): string {
  return subKey.replace(/\s/g, '_');
}

function filterPath(basePath: string, filter: string, subKey?: string): string {
  if (!subKey) {
    return `${basePath}?filter=${filter}`;
  }

  return `${basePath}?filter=${filter}&subFilter=${subFilterSlug(subKey)}`;
}

/** Collapse nested filter groups when only one parent has children (stores/contacts). */
function collapseLoneNestedFilterGroups(children: NavItem[]): NavItem[] {
  if (children.length === 1 && (children[0].children?.length ?? 0) > 0) {
    return children[0].children ?? [];
  }

  const nestedParents = children.filter((child) => (child.children?.length ?? 0) > 0);

  if (nestedParents.length !== 1) {
    return children;
  }

  const [onlyNested] = nestedParents;
  const flatSiblings = children.filter((child) => !(child.children?.length ?? 0));

  // Leads mix many flat status filters with one owner group — keep the group label.
  if (flatSiblings.length > 2) {
    return children;
  }

  return [...flatSiblings, ...(onlyNested.children ?? [])];
}

/**
 * When a section contains a single parent with children (e.g. Admin → Settings),
 * show the children directly under the section label.
 */
export function unwrapSingleParentSections(
  navigation: Array<NavItem | NavSection>,
): Array<NavItem | NavSection> {
  const result: Array<NavItem | NavSection> = [];
  let pendingSection: NavSection | null = null;
  let sectionItems: NavItem[] = [];

  const flushSection = () => {
    if (!pendingSection) {
      return;
    }

    result.push(pendingSection);

    if (
      sectionItems.length === 1
      && (sectionItems[0].children?.length ?? 0) > 0
      && ! sectionItems[0].expandOnly
    ) {
      result.push(...(sectionItems[0].children ?? []));
    } else {
      result.push(...sectionItems);
    }

    pendingSection = null;
    sectionItems = [];
  };

  for (const entry of navigation) {
    if ('type' in entry && entry.type === 'section') {
      flushSection();
      pendingSection = entry;
      continue;
    }

    if (pendingSection) {
      sectionItems.push(entry as NavItem);
    } else {
      result.push(entry);
    }
  }

  flushSection();

  return result;
}

function buildReportFilterChildren(
  resource: ReportResource,
  basePath: string,
  appConfig: AppConfig | null,
  isUiDisabled: (domain: 'leads' | 'stores' | 'contacts', key: string) => boolean,
): NavItem[] {
  const menus = menusForResource(appConfig, resource);
  const uiDomain = resource;
  const menuKeys = Object.keys(menus.menuItems ?? {});
  const children: NavItem[] = [];
  const flatGroups = new Set(FLAT_SUBMENU_GROUPS[resource]);

  for (const key of menuKeys) {
    if ((SIDEBAR_EXCLUDED_MENU_KEYS[resource] ?? []).includes(key)) {
      continue;
    }

    if (isUiDisabled(uiDomain, key)) {
      continue;
    }

    const item = menus.menuItems[key];
    const subItems = menus.subMenuItems?.[key] ?? {};
    const subEntries = Object.entries(subItems).filter(([subKey]) => !isUiDisabled(uiDomain, subKey));

    if (subEntries.length > 0 && flatGroups.has(key)) {
      const skipGroupHeader = resource === 'stores' && key === 'store_status';

      if (! skipGroupHeader) {
        if (resource === 'leads' && key === 'lead_status') {
          children.push({
            id: `${resource}-${key}`,
            label: item.label ?? key,
            path: basePath,
          });
        } else {
          children.push({
            id: `${resource}-${key}`,
            label: item.label ?? key,
            path: filterPath(basePath, key),
          });
        }
      }

      for (const [subKey, subItem] of subEntries) {
        children.push({
          id: `${resource}-${key}-${subKey}`,
          label: subItem.label ?? subKey,
          path: filterPath(basePath, key, subKey),
        });
      }

      continue;
    }

    if (subEntries.length > 0) {
      children.push({
        id: `${resource}-${key}`,
        label: item.label ?? key,
        path: filterPath(basePath, key),
        children: subEntries.map(([subKey, subItem]) => ({
          id: `${resource}-${key}-${subKey}`,
          label: subItem.label ?? subKey,
          path: filterPath(basePath, key, subKey),
        })),
      });

      continue;
    }

    children.push({
      id: `${resource}-${key}`,
      label: item.label ?? key,
      path: filterPath(basePath, key),
    });
  }

  return children;
}

function expandNavigationEntry(
  entry: NavItem | NavSection,
  appConfig: AppConfig | null,
  isUiDisabled: (domain: 'leads' | 'stores' | 'contacts', key: string) => boolean,
): Array<NavItem | NavSection> {
  if ('type' in entry && entry.type === 'section') {
    return [entry];
  }

  const item = entry as NavItem;

  if (isReportResource(item.id)) {
    const rawChildren = buildReportFilterChildren(item.id, item.path, appConfig, isUiDisabled);
    const filterChildren =
      item.id === 'stores' ? rawChildren : collapseLoneNestedFilterGroups(rawChildren);

    if (filterChildren.length === 0) {
      return [item];
    }

    return [
      {
        ...item,
        expandOnly: item.id === 'stores',
        children: filterChildren,
      },
    ];
  }

  return [item];
}

export function buildNavigationTree(
  navigation: Array<NavItem | NavSection>,
  appConfig: AppConfig | null,
  isUiDisabled: (domain: 'leads' | 'stores' | 'contacts', key: string) => boolean,
): Array<NavItem | NavSection> {
  const withFilters = navigation.flatMap((entry) =>
    expandNavigationEntry(entry, appConfig, isUiDisabled),
  );

  return unwrapSingleParentSections(withFilters);
}
