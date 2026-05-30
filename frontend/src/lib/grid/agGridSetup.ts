import {
  ClientSideRowModelApiModule,
  ClientSideRowModelModule,
  ColumnApiModule,
  EventApiModule,
  LocaleModule,
  ModuleRegistry,
  RenderApiModule,
  RowApiModule,
  RowSelectionModule,
  RowStyleModule,
  ScrollApiModule,
  ValidationModule,
  themeQuartz,
} from 'ag-grid-community';

/**
 * Minimal community modules for FilGrid (client-side row model, sort, resize,
 * pinned selection column, scroll-to-load-more, row click). Avoids AllCommunityModule
 * so editors, filters, infinite row model, CSV export, etc. are tree-shaken out.
 */
const filGridModules = [
  ClientSideRowModelModule,
  ClientSideRowModelApiModule,
  ColumnApiModule,
  RowApiModule,
  ScrollApiModule,
  RenderApiModule,
  EventApiModule,
  RowSelectionModule,
  RowStyleModule,
  LocaleModule,
  ...(import.meta.env.DEV ? [ValidationModule] : []),
];

ModuleRegistry.registerModules(filGridModules);

function readCssVar(name: string, fallback: string): string {
  if (typeof document === 'undefined') {
    return fallback;
  }

  const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

  return value || fallback;
}

/** Reads semantic tokens from `index.css` so grid chrome tracks light/dark theme. */
export function getFilGridTheme(_isDark = false) {
  return themeQuartz.withParams({
    backgroundColor: readCssVar('--color-surface', '#ffffff'),
    foregroundColor: readCssVar('--color-foreground', 'hsl(222 28% 11%)'),
    headerBackgroundColor: readCssVar('--color-surface-muted', 'hsl(214 28% 96%)'),
    borderColor: readCssVar('--color-border', 'hsl(214 18% 88%)'),
    rowHoverColor: readCssVar('--color-accent-soft', '#eef4ff'),
    selectedRowBackgroundColor: readCssVar('--color-accent-soft-border', '#c7d9f8'),
    fontFamily: "'Inter', ui-sans-serif, system-ui, sans-serif",
    fontSize: 14,
    headerFontSize: 14,
    spacing: 8,
    wrapperBorderRadius: 12,
  });
}

/** @deprecated Use getFilGridTheme() — theme follows CSS variables automatically. */
export const filGridTheme = getFilGridTheme();
