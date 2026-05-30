import { useCallback, useState } from 'react';
import { queryGrid } from '@/lib/api/grid';
import type { GridSearchInterpretation } from '@/lib/api/gridSearch';

export interface LeadSearchResult {
  id: number;
  title: string;
}

interface UseInterpretedLeadSearchOptions {
  limit?: number;
}

export function useInterpretedLeadSearch({ limit = 15 }: UseInterpretedLeadSearchOptions = {}) {
  const [query, setQuery] = useState('');
  const [interpretation, setInterpretation] = useState<GridSearchInterpretation | null>(null);
  const [results, setResults] = useState<LeadSearchResult[]>([]);

  const handleInterpretation = useCallback(
    (result: GridSearchInterpretation) => {
      setInterpretation(result);
      setQuery(result.original_query);

      void queryGrid('leads', {
        limit,
        search: result.query.search,
        filters: result.query.filters,
        sort: result.query.sort,
      })
        .then((response) => {
          setResults(
            response.hits.hits.map((hit) => ({
              id: Number(hit._source.id),
              title: String(hit._source.post_title ?? hit._source.title ?? 'Untitled'),
            })),
          );
        })
        .catch(() => setResults([]));
    },
    [limit],
  );

  const clear = useCallback(() => {
    setInterpretation(null);
    setQuery('');
    setResults([]);
  }, []);

  return {
    query,
    setQuery,
    interpretation,
    results,
    handleInterpretation,
    clear,
  };
}
