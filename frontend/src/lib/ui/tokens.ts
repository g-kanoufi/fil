/**
 * FIL design tokens — Tailwind class strings backed by CSS variables in `index.css`.
 *
 * Rules:
 * - Use these tokens instead of raw palette pairs (e.g. bg-brand-50 + text-brand-700).
 * - Do not add `dark:` overrides for tokens defined here — theme switching is in CSS vars.
 * - For new tinted UI, extend `index.css` + this file together.
 */

import { cn } from '@/lib/cn';

/** Inline text links (router or anchor). */
export const textLink = 'font-medium text-link hover:underline';

/** Breadcrumb / back links (slightly lighter weight). */
export const textLinkPlain = 'text-link hover:underline';

/** Shared focus ring for icon buttons, chips, and native selects. */
export const focusRing =
  'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus/25 focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--body-bg-color)]';

/** Icon / ghost control (header menu, sort toggle). */
export const iconButton =
  'rounded-lg p-2 text-muted transition-colors hover:bg-surface-muted hover:text-foreground';

export const iconButtonClasses = cn(iconButton, focusRing);

/** Standard bordered card/panel. */
export const card =
  'rounded-xl border border-border bg-surface shadow-[var(--shadow-card)]';

/** Login panel — gradient frame + inner card. */
export const loginShell =
  'w-full max-w-md rounded-xl bg-gradient-to-br from-primary/25 via-primary/10 to-accent-soft-border p-px shadow-[var(--shadow-elevated)]';

export const loginInner = 'rounded-[11px] border-0 bg-surface shadow-none';

/** Form controls — combine `formControl` + `formFocus` (+ optional `formControlError`). */
export const formControl =
  'block w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-foreground placeholder:text-muted';

export const formFocus =
  'focus:border-focus focus:outline-none focus:ring-2 focus:ring-focus/20';

export const formControlError =
  'border-error focus:border-error focus:ring-error/20';

/** Compact native select matching form fields. */
export const selectControl = cn(formControl, formFocus, 'w-auto py-2');

/** Active tab (segmented nav, FDD manager). */
export const tabActive = 'bg-primary text-primary-fg';

/** Filter / toggle chips (pill buttons). */
export const chipActive =
  'border-primary bg-primary text-primary-fg';

export const chipInactive =
  'border-border bg-surface text-foreground hover:border-primary hover:bg-accent-soft';

export const chipButton = cn(
  'rounded-xl border px-4 py-3 text-left transition-colors',
  focusRing,
);

/** Metric / filter tile numbers */
export const metricValueActive = 'text-2xl font-semibold text-accent-soft-fg';

export const metricValueInactive = 'text-2xl font-semibold text-foreground';

export const metricLabel = 'mt-1 text-sm text-muted';

export const chipCountActive = 'rounded-full bg-primary-fg/20 px-1.5 text-xs text-primary-fg';

export const chipCountInactive =
  'rounded-full bg-surface-muted px-1.5 text-xs text-muted';

/** Segmented toggle (e.g. email vs SMS). */
export const segmentActive = cn(chipActive, 'rounded-full border px-3 py-1 text-sm', focusRing);

export const segmentInactive = cn(chipInactive, 'rounded-full border px-3 py-1 text-sm', focusRing);

/** Tinted surfaces (badges, callouts, metric cards). */
export const surface = {
  accentCallout:
    'border border-accent-soft-border bg-accent-soft text-accent-soft-fg',
  accentCalloutTitle: 'text-sm font-medium text-accent-soft-fg',
  accentCalloutMeta: 'mt-1 text-xs text-accent-soft-muted',
  accentCalloutAction:
    'shrink-0 text-xs font-medium text-accent-soft-fg underline-offset-2 hover:underline',
  accentHover: 'hover:border-primary hover:bg-accent-soft',
  accentSelected: 'border-primary bg-accent-soft',
  accentMetric: 'text-2xl font-semibold text-accent-soft-fg',
  recordCountChip:
    'inline-flex items-center rounded-full bg-accent-soft px-3 py-1 text-sm font-medium text-accent-soft-fg',
  statBrandAccent:
    'border border-accent-soft-border bg-accent-soft text-accent-soft-fg',
  themePickerActive: 'border-primary bg-accent-soft text-accent-soft-fg',
  tabInactive: 'bg-surface text-foreground hover:bg-accent-soft',
  listItemSelected: 'bg-accent-soft font-medium text-foreground',
  listItemHover: 'hover:bg-accent-soft',
  fileInputButton:
    'file:mr-3 file:rounded-md file:border-0 file:bg-accent-soft file:px-3 file:py-2 file:text-sm file:font-medium file:text-accent-soft-fg',
  linkCardHover: 'hover:border-primary hover:bg-accent-soft',
  aiBubble: 'ml-8 bg-accent-soft text-foreground',
} as const;

export const badge = {
  default: 'bg-surface-muted text-foreground',
  success: 'bg-tone-success-bg text-tone-success-fg',
  warning: 'bg-tone-warning-bg text-tone-warning-fg',
  error: 'bg-tone-error-bg text-tone-error-fg',
  info: 'bg-tone-info-bg text-tone-info-fg',
} as const;

export const alert = {
  info: 'border border-tone-info-border bg-tone-info-bg text-tone-info-fg',
  success:
    'border border-tone-success-border bg-tone-success-bg text-tone-success-fg',
  error: 'border border-tone-error-border bg-tone-error-bg text-tone-error-fg',
} as const;

export const button = {
  primary:
    'bg-primary text-primary-fg hover:bg-primary-hover focus-visible:ring-focus disabled:bg-primary/60',
  secondary:
    'border border-border bg-surface text-foreground hover:bg-surface-muted focus-visible:ring-border-strong',
  ghost:
    'text-muted hover:bg-surface-muted hover:text-foreground focus-visible:ring-border-strong',
  danger:
    'bg-error text-white hover:bg-red-600 focus-visible:ring-red-400 disabled:bg-error/60',
  base:
    'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70',
} as const;

/** @deprecated Use `surface` — kept for existing imports. */
export const surfaces = surface;

/** @deprecated Use `badge` — kept for existing imports. */
export const badgeVariants = badge;

/** @deprecated Use `alert` — kept for existing imports. */
export const alertVariants = alert;
