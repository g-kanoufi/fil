import { useCallback, useEffect, useState } from 'react';
import { ActivityFeedList } from '@/components/activity/ActivityFeedList';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { fetchActivityFeed, type ActivityItem } from '@/lib/api/activity';

const DAY_OPTIONS = [7, 30, 90];

export function HistoryPage() {
  const [days, setDays] = useState(30);
  const [items, setItems] = useState<ActivityItem[]>([]);
  const [cursor, setCursor] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadFeed = useCallback(async (append = false, nextCursor: string | null = null) => {
    if (append) {
      setLoadingMore(true);
    } else {
      setLoading(true);
    }

    setError(null);

    try {
      const response = await fetchActivityFeed({
        days,
        limit: 25,
        cursor: append ? nextCursor ?? undefined : undefined,
      });

      setItems((current) => (append ? [...current, ...response.data] : response.data));
      setCursor(response.meta.next_cursor);
      setHasMore(response.meta.has_more);
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load activity');
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, [days]);

  useEffect(() => {
    void loadFeed(false);
  }, [loadFeed]);

  return (
    <>
      <PageHeader
        title="Activity history"
        description="Recent staff actions across leads, stores, and settings."
      />

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <label htmlFor="activity-days" className="text-sm text-muted">
          Show last
        </label>
        <select
          id="activity-days"
          value={days}
          onChange={(event) => setDays(Number(event.target.value))}
          className="rounded-lg border border-border bg-surface px-3 py-2 text-sm"
        >
          {DAY_OPTIONS.map((option) => (
            <option key={option} value={option}>
              {option} days
            </option>
          ))}
        </select>
      </div>

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      {loading ? <LoadingState label="Loading activity…" /> : null}

      {!loading ? (
        <Card>
          <CardHeader title="Recent activity" />
          <ActivityFeedList items={items} />
          {hasMore ? (
            <div className="mt-4">
              <Button
                variant="secondary"
                size="sm"
                disabled={loadingMore}
                onClick={() => void loadFeed(true, cursor)}
              >
                {loadingMore ? 'Loading…' : 'Load more'}
              </Button>
            </div>
          ) : null}
        </Card>
      ) : null}
    </>
  );
}
