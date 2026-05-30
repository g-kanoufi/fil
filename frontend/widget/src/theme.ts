/** Theme keys accepted from API, data attributes, postMessage, or window.FIL_WIDGET_THEME */
export type WidgetTheme = Record<string, string>;

/** Maps theme object keys to --fil-* CSS custom properties */
const THEME_VAR_MAP: Record<string, string> = {
  fontFamily: '--fil-font-family',
  font_family: '--fil-font-family',
  text: '--fil-text',
  label: '--fil-label',
  muted: '--fil-muted',
  surface: '--fil-surface',
  border: '--fil-border',
  borderFocus: '--fil-border-focus',
  border_focus: '--fil-border-focus',
  primary: '--fil-primary',
  primaryHover: '--fil-primary-hover',
  primary_hover: '--fil-primary-hover',
  primaryFg: '--fil-primary-fg',
  primary_fg: '--fil-primary-fg',
  error: '--fil-error',
  errorBg: '--fil-error-bg',
  error_bg: '--fil-error-bg',
  errorBorder: '--fil-error-border',
  error_border: '--fil-error-border',
  success: '--fil-success',
  successBg: '--fil-success-bg',
  success_bg: '--fil-success-bg',
  successBorder: '--fil-success-border',
  success_border: '--fil-success-border',
  inputBg: '--fil-input-bg',
  input_bg: '--fil-input-bg',
  inputRadius: '--fil-input-radius',
  input_radius: '--fil-input-radius',
  buttonRadius: '--fil-button-radius',
  button_radius: '--fil-button-radius',
  gap: '--fil-gap',
  focusRing: '--fil-focus-ring',
  focus_ring: '--fil-focus-ring',
};

/** data-fil-* attributes on the mount element (camelCase dataset keys → theme keys) */
const DATASET_THEME_MAP: Record<string, keyof typeof THEME_VAR_MAP> = {
  filFontFamily: 'fontFamily',
  filText: 'text',
  filLabel: 'label',
  filMuted: 'muted',
  filSurface: 'surface',
  filBorder: 'border',
  filBorderFocus: 'borderFocus',
  filPrimary: 'primary',
  filPrimaryHover: 'primaryHover',
  filPrimaryFg: 'primaryFg',
  filError: 'error',
  filErrorBg: 'errorBg',
  filErrorBorder: 'errorBorder',
  filSuccess: 'success',
  filSuccessBg: 'successBg',
  filSuccessBorder: 'successBorder',
  filInputBg: 'inputBg',
  filInputRadius: 'inputRadius',
  filButtonRadius: 'buttonRadius',
  filGap: 'gap',
  filFocusRing: 'focusRing',
};

export function themeToStyleProperties(theme: WidgetTheme): Record<string, string> {
  const properties: Record<string, string> = {};

  for (const [key, value] of Object.entries(theme)) {
    if (typeof value !== 'string' || value.trim() === '') {
      continue;
    }

    const cssVar = THEME_VAR_MAP[key];
    if (cssVar) {
      properties[cssVar] = value;
    }
  }

  return properties;
}

export function parseDatasetTheme(mount: HTMLElement): WidgetTheme {
  const theme: WidgetTheme = {};

  const jsonTheme = mount.dataset.filTheme;
  if (jsonTheme) {
    try {
      const parsed = JSON.parse(jsonTheme) as unknown;
      if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
        Object.assign(theme, parsed);
      }
    } catch {
      // ignore invalid JSON
    }
  }

  for (const [datasetKey, themeKey] of Object.entries(DATASET_THEME_MAP)) {
    const value = mount.dataset[datasetKey as keyof DOMStringMap];
    if (typeof value === 'string' && value !== '') {
      theme[themeKey] = value;
    }
  }

  return theme;
}

export function applyTheme(mount: HTMLElement, ...sources: Array<WidgetTheme | null | undefined>): void {
  const merged: WidgetTheme = {};

  for (const source of sources) {
    if (source) {
      Object.assign(merged, source);
    }
  }

  const properties = themeToStyleProperties(merged);
  for (const [cssVar, value] of Object.entries(properties)) {
    mount.style.setProperty(cssVar, value);
  }
}

export function listenForThemeUpdates(mount: HTMLElement): () => void {
  const handler = (event: MessageEvent): void => {
    const data = event.data as { type?: string; theme?: WidgetTheme } | null;
    if (!data || data.type !== 'fil-widget-theme' || !data.theme) {
      return;
    }

    applyTheme(mount, data.theme);
  };

  window.addEventListener('message', handler);

  return () => window.removeEventListener('message', handler);
}

declare global {
  interface Window {
    FIL_WIDGET_THEME?: WidgetTheme;
  }
}
