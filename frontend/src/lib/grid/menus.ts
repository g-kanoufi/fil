import type { EntityMenus } from '@/lib/grid/filters';
import type { AppConfig } from '@/types/auth';

export function menusForResource(appConfig: AppConfig | null, resource: string): EntityMenus {
  const raw = appConfig?.menus.menus_with_columns?.[resource] as EntityMenus | undefined;

  return raw ?? { menuItems: {}, subMenuItems: {} };
}
