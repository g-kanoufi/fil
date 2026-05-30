/** Normalized client branding from app-config or public branding API. */
export interface ClientBrand {
  brandName: string;
  logoUrl: string | null;
  faviconUrl: string | null;
  clientBranding: boolean;
  primaryColor: string | null;
  linkColor: string | null;
}

const CSS_VAR_KEYS = [
  '--color-primary',
  '--color-link',
  '--color-primary-hover',
  '--color-focus',
] as const;

const DEFAULT_BRAND: ClientBrand = {
  brandName: 'FIL',
  logoUrl: null,
  faviconUrl: null,
  clientBranding: true,
  primaryColor: null,
  linkColor: null,
};

function readString(options: Record<string, unknown>, key: string): string | null {
  const value = options[key];
  return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
}

function readBoolean(options: Record<string, unknown>, key: string, fallback: boolean): boolean {
  const value = options[key];
  if (typeof value === 'boolean') {
    return value;
  }
  return fallback;
}

/** Parse legacy `client_settings` / app-config options into a normalized brand. */
export function parseClientBrand(options: Record<string, unknown> | null | undefined): ClientBrand {
  if (!options) {
    return DEFAULT_BRAND;
  }

  const primary =
    readString(options, 'highlightColor')
    ?? readString(options, 'linkColor')
    ?? readString(options, 'topBarColor');

  return {
    brandName: readString(options, 'brandName') ?? DEFAULT_BRAND.brandName,
    logoUrl: readString(options, 'logoUrl'),
    faviconUrl: readString(options, 'faviconUrl'),
    clientBranding: readBoolean(options, 'clientBranding', true),
    primaryColor: primary,
    linkColor: readString(options, 'linkColor') ?? primary,
  };
}

function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
  const normalized = hex.replace('#', '');
  if (!/^[0-9a-f]{6}$/i.test(normalized)) {
    return null;
  }

  return {
    r: Number.parseInt(normalized.slice(0, 2), 16),
    g: Number.parseInt(normalized.slice(2, 4), 16),
    b: Number.parseInt(normalized.slice(4, 6), 16),
  };
}

function rgbToHex(r: number, g: number, b: number): string {
  return `#${[r, g, b].map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
}

/** Darken a hex color for hover states (simple RGB scale). */
export function darkenHex(hex: string, amount = 0.12): string | null {
  const rgb = hexToRgb(hex);
  if (!rgb) {
    return null;
  }

  const factor = 1 - amount;

  return rgbToHex(
    Math.round(rgb.r * factor),
    Math.round(rgb.g * factor),
    Math.round(rgb.b * factor),
  );
}

export function brandCssVariables(brand: ClientBrand): Record<string, string> {
  if (!brand.clientBranding) {
    return {};
  }

  const primary = brand.primaryColor ?? brand.linkColor;
  if (!primary) {
    return {};
  }

  const link = brand.linkColor ?? primary;
  const hover = darkenHex(primary) ?? primary;

  return {
    '--color-primary': primary,
    '--color-link': link,
    '--color-primary-hover': hover,
    '--color-focus': primary,
  };
}

export function applyBrandCssVariables(brand: ClientBrand): void {
  const root = document.documentElement;
  const next = brandCssVariables(brand);

  for (const key of CSS_VAR_KEYS) {
    if (key in next) {
      root.style.setProperty(key, next[key]);
    } else {
      root.style.removeProperty(key);
    }
  }
}

export function applyDocumentBrand(brand: ClientBrand): void {
  applyBrandCssVariables(brand);
  document.title = brand.brandName;

  const href = brand.faviconUrl;
  let link = document.querySelector<HTMLLinkElement>('link[rel="icon"]');

  if (!href) {
    link?.remove();
    return;
  }

  if (!link) {
    link = document.createElement('link');
    link.rel = 'icon';
    document.head.appendChild(link);
  }

  link.href = href;
}
