import type { FieldConfig, FieldDef } from '@/lib/api/fields';

export type FieldChoice = {
  value: string;
  label: string;
  aliases?: string[];
  meta?: Record<string, unknown>;
};

export function choiceOptions(field: FieldDef): Array<{ value: string; label: string }> {
  return normalizeChoices(field.config?.choices);
}

export function normalizeChoices(
  choices: FieldConfig['choices'] | undefined,
): Array<{ value: string; label: string }> {
  if (!choices) {
    return [];
  }

  if (Array.isArray(choices)) {
    return choices.map((item) => ({
      value: String(item.value),
      label: String(item.label ?? item.value),
    }));
  }

  return Object.entries(choices).map(([value, label]) => ({
    value,
    label: String(label),
  }));
}

/** Parse admin textarea: `value=Label` or `value=Label|alias1,alias2` */
export function parseChoiceLines(text: string): FieldChoice[] {
  const choices: FieldChoice[] = [];

  for (const line of text.split('\n')) {
    const trimmed = line.trim();
    if (!trimmed) {
      continue;
    }

    const [left, ...rest] = trimmed.split('=');
    const value = left.trim();
    if (!value) {
      continue;
    }

    const rhs = rest.length > 0 ? rest.join('=').trim() : value;
    const [labelPart, aliasPart] = rhs.split('|').map((part) => part.trim());
    const label = labelPart || value;
    const aliases = aliasPart
      ? aliasPart.split(',').map((alias) => alias.trim()).filter(Boolean)
      : undefined;

    choices.push({
      value,
      label,
      ...(aliases && aliases.length > 0 ? { aliases } : {}),
    });
  }

  return choices;
}

export function formatChoiceLines(choices: FieldConfig['choices'] | undefined): string {
  const normalized = normalizeChoices(choices);

  return normalized.map(({ value, label }) => (value === label ? value : `${value}=${label}`)).join('\n');
}

export function tierOneFieldMap(groups: Array<{ fields: FieldDef[] }>, keys: string[]): Map<string, FieldDef> {
  const map = new Map<string, FieldDef>();

  for (const group of groups) {
    for (const field of group.fields) {
      if (keys.includes(field.key) && field.storage === 'column' && field.status === 'active') {
        map.set(field.key, field);
      }
    }
  }

  return map;
}
