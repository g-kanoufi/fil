import { describe, expect, it } from 'vitest';
import {
  applyBrandCssVariables,
  brandCssVariables,
  darkenHex,
  parseClientBrand,
} from '@/lib/branding/clientBranding';

describe('parseClientBrand', () => {
  it('maps legacy option keys to normalized brand', () => {
    const brand = parseClientBrand({
      brandName: 'Acme',
      highlightColor: '#53387b',
      logoUrl: 'https://example.com/logo.png',
      faviconUrl: '/favicon.ico',
    });

    expect(brand.brandName).toBe('Acme');
    expect(brand.primaryColor).toBe('#53387b');
    expect(brand.logoUrl).toBe('https://example.com/logo.png');
  });

  it('skips CSS overrides when client branding is disabled', () => {
    const brand = parseClientBrand({
      clientBranding: false,
      highlightColor: '#53387b',
    });

    expect(brandCssVariables(brand)).toEqual({});
  });
});

describe('darkenHex', () => {
  it('darkens a hex color for hover states', () => {
    expect(darkenHex('#ffffff')).toBe('#e0e0e0');
  });
});

describe('applyBrandCssVariables', () => {
  it('sets primary tokens on the document root', () => {
    const brand = parseClientBrand({ highlightColor: '#0f766e' });
    applyBrandCssVariables(brand);

    expect(document.documentElement.style.getPropertyValue('--color-primary').trim()).toBe('#0f766e');
  });
});
