import type { AppConfig, NavItem, NavSection } from '@/types/auth';
import { menusForResource } from '@/lib/grid/menus';

const REPORT_RESOURCES = ['stores', 'contacts', 'leads'] as const;

type ReportResource = (typeof REPORT_RESOURCES)[number];

/** Submenu values rendered as siblings under the report root (fl-react parity — leads only). */
export const FLAT_SUBMENU_GROUPS: Record<ReportResource, readonly string[]> = {
  leads: ['lead_status'],
  stores: [],
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
    if (isUiDisabled(uiDomain, key)) {
      continue;
    }

    const item = menus.menuItems[key];
    const subItems = menus.subMenuItems?.[key] ?? {};
    const subEntries = Object.entries(subItems).filter(([subKey]) => !isUiDisabled(uiDomain, subKey));

    if (subEntries.length > 0 && flatGroups.has(key)) {
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

export function buildNavigationTree(
  navigation: Array<NavItem | NavSection>,
  appConfig: AppConfig | null,
  isUiDisabled: (domain: 'leads' | 'stores' | 'contacts', key: string) => boolean,
): Array<NavItem | NavSection> {
  return navigation.map((entry) => {
    if ('type' in entry && entry.type === 'section') {
      return entry;
    }

    const item = entry as NavItem;

    if (isReportResource(item.id)) {
      const filterChildren = buildReportFilterChildren(item.id, item.path, appConfig, isUiDisabled);

      if (filterChildren.length === 0) {
        return item;
      }

      return {
        ...item,
        children: filterChildren,
      };
    }

    return item;
  });
}
