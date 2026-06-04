import type { AppConfig } from '@/types/auth';

export interface LeadApplicationStatusChoice {
  value: string;
  label: string;
  slug: string;
  category: string | null;
  closed: boolean;
  pipeline_phase: number | null;
  sort: number;
  filter_values: string[];
}

export interface LeadApplicationStatusConfig {
  choices: LeadApplicationStatusChoice[];
  groups: {
    active: { submenu_key: string; values: string[]; filter_values: string[] };
    won: { submenu_key: string; values: string[]; filter_values: string[] };
    closed: { values: string[]; filter_values: string[] };
  };
}

export function leadApplicationStatusConfig(
  appConfig: AppConfig | null,
): LeadApplicationStatusConfig | null {
  const raw = appConfig?.lead_application_status;

  if (!raw || !Array.isArray(raw.choices)) {
    return null;
  }

  return raw as LeadApplicationStatusConfig;
}

export function filterValuesForStatusGroup(
  appConfig: AppConfig | null,
  group: 'active' | 'won' | 'closed',
): string[] {
  const config = leadApplicationStatusConfig(appConfig);

  if (!config) {
    return [];
  }

  return config.groups[group]?.filter_values ?? [];
}

export function filterValuesForChoiceSlug(
  appConfig: AppConfig | null,
  slug: string,
): string[] {
  const config = leadApplicationStatusConfig(appConfig);

  if (!config) {
    return [];
  }

  const match = config.choices.find((choice) => choice.slug === slug);

  return match?.filter_values ?? [];
}

export function isWonApplicationStatus(
  appConfig: AppConfig | null,
  stored: string | null | undefined,
): boolean {
  if (!stored || stored.trim() === '') {
    return false;
  }

  const config = leadApplicationStatusConfig(appConfig);

  if (!config) {
    return stored.toLowerCase().includes('award');
  }

  const canonical = config.choices.find(
    (choice) =>
      choice.value === stored
      || choice.filter_values.includes(stored)
      || choice.slug === stored.replace(/[-\s]+/g, '_').toLowerCase(),
  );

  if (canonical) {
    return canonical.category === 'won';
  }

  return config.groups.won.values.includes(stored)
    || config.groups.won.filter_values.includes(stored);
}
