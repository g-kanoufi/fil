import { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';

export interface GridQueryParams {
  search: string;
  filter: string;
  subFilter: string;
  leadtemp: string;
  sortField: string;
  sortDirection: 'asc' | 'desc';
}

const DEFAULT_SORT: Record<string, { field: string; direction: 'asc' | 'desc' }> = {
  leads: { field: 'updated_at', direction: 'desc' },
  stores: { field: 'updated_at', direction: 'desc' },
  contacts: { field: 'updated_at', direction: 'desc' },
};

export function useGridQueryParams(resource: 'leads' | 'stores' | 'contacts') {
  const [searchParams, setSearchParams] = useSearchParams();
  const defaults = DEFAULT_SORT[resource];

  const params = useMemo<GridQueryParams>(() => {
    const sortField = searchParams.get('sortField') || defaults.field;
    const sortDir = searchParams.get('sortDirection');

    return {
      search: searchParams.get('search') || '',
      filter: searchParams.get('filter') || '',
      subFilter: searchParams.get('subFilter') || '',
      leadtemp: searchParams.get('leadtemp') || '',
      sortField,
      sortDirection: sortDir === 'asc' ? 'asc' : 'desc',
    };
  }, [defaults.field, searchParams]);

  const setParam = useCallback(
    (key: string, value: string | null) => {
      setSearchParams(
        (current) => {
          const next = new URLSearchParams(current);
          if (value === null || value === '') {
            next.delete(key);
          } else {
            next.set(key, value);
          }
          return next;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  const setSearch = useCallback((value: string) => setParam('search', value || null), [setParam]);

  const setFilter = useCallback(
    (filter: string, subFilter?: string) => {
      setSearchParams(
        (current) => {
          const next = new URLSearchParams(current);
          if (!filter) {
            next.delete('filter');
            next.delete('subFilter');
          } else {
            next.set('filter', filter);
            if (subFilter) {
              next.set('subFilter', subFilter);
            } else {
              next.delete('subFilter');
            }
          }
          return next;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  const setLeadTemp = useCallback(
    (value: string) => setParam('leadtemp', value || null),
    [setParam],
  );

  const setSort = useCallback(
    (field: string, direction: 'asc' | 'desc') => {
      setSearchParams(
        (current) => {
          const next = new URLSearchParams(current);
          next.set('sortField', field);
          next.set('sortDirection', direction);
          return next;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  const applyNavigation = useCallback(
    (navigation: {
      search?: string;
      filter?: string;
      subFilter?: string;
      leadtemp?: string;
      sortField?: string;
      sortDirection?: 'asc' | 'desc';
    }) => {
      setSearchParams(
        (current) => {
          const next = new URLSearchParams(current);

          if (navigation.search) {
            next.set('search', navigation.search);
          } else {
            next.delete('search');
          }

          if (navigation.filter) {
            next.set('filter', navigation.filter);
            if (navigation.subFilter) {
              next.set('subFilter', navigation.subFilter);
            } else {
              next.delete('subFilter');
            }
          } else {
            next.delete('filter');
            next.delete('subFilter');
          }

          if (navigation.leadtemp) {
            next.set('leadtemp', navigation.leadtemp);
          } else {
            next.delete('leadtemp');
          }

          if (navigation.sortField) {
            next.set('sortField', navigation.sortField);
          }

          if (navigation.sortDirection) {
            next.set('sortDirection', navigation.sortDirection);
          }

          return next;
        },
        { replace: true },
      );
    },
    [setSearchParams],
  );

  const queryKey = useMemo(
    () =>
      JSON.stringify({
        resource,
        search: params.search,
        filter: params.filter,
        subFilter: params.subFilter,
        leadtemp: params.leadtemp,
        sortField: params.sortField,
        sortDirection: params.sortDirection,
      }),
    [params, resource],
  );

  return { params, setSearch, setFilter, setLeadTemp, setSort, applyNavigation, queryKey };
}
