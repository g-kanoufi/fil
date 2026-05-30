import type { GridSearchInterpretation } from '@/lib/api/gridSearch';

export function applyGridSearchInterpretation(
  result: GridSearchInterpretation,
  applyNavigation: (navigation: {
    search?: string;
    filter?: string;
    subFilter?: string;
    leadtemp?: string;
    sortField?: string;
    sortDirection?: 'asc' | 'desc';
  }) => void,
): void {
  const { navigation, query } = result;

  applyNavigation({
    search: navigation.search ?? query.search ?? result.original_query,
    filter: navigation.filter ?? '',
    subFilter: navigation.subFilter,
    leadtemp: navigation.leadtemp ?? '',
    sortField: navigation.sortField ?? query.sort?.[0]?.field,
    sortDirection: navigation.sortDirection ?? query.sort?.[0]?.direction,
  });
}

export function mergeInterpretationFilters(
  baseFilters: Record<string, unknown>,
  interpretation: GridSearchInterpretation | null,
): Record<string, unknown> {
  if (!interpretation?.query.filters) {
    return baseFilters;
  }

  return {
    ...baseFilters,
    ...interpretation.query.filters,
  };
}
