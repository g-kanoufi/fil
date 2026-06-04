import { useCallback, useEffect, useState } from 'react';
import { ActivityFeedList } from '@/components/activity/ActivityFeedList';
import { ExportCsvButton } from '@/components/export/ExportCsvButton';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { PageHeader } from '@/components/ui/PageHeader';
import { cn } from '@/lib/cn';
import {
  downloadActivityExport,
  fetchActivityFeed,
  fetchAllActivityFeed,
  type ActivityItem,
} from '@/lib/api/activity';
import { downloadActivityCsv } from '@/lib/export/activityCsv';
import { activityFeedExportFilename } from '@/lib/export/filenames';
import { focusRing, segmentActive, segmentInactive } from '@/lib/ui/tokens';

const DAY_OPTIONS = [7, 30, 90];

type FeedMode = 'actions' | 'navigation';

export function HistoryPage() {
  const [days, setDays] = useState(30);
  const [feedMode, setFeedMode] = useState<FeedMode>('actions');
  const [items, setItems] = useState<ActivityItem[]>([]);
  const [cursor, setCursor] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [exportingServer, setExportingServer] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [navigationTableReady, setNavigationTableReady] = useState<boolean | null>(null);

  const showPageVisits = feedMode === 'navigation';

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
        category: showPageVisits ? 'navigation' : undefined,
      });

      setItems((current) => (append ? [...current, ...response.data] : response.data));
      setCursor(response.meta.next_cursor);
      setHasMore(response.meta.has_more);
      setNavigationTableReady(
        showPageVisits ? (response.meta.navigation_table_ready ?? true) : null,
      );
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load activity');
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  }, [days, showPageVisits]);

  useEffect(() => {
    void loadFeed(false);
  }, [loadFeed]);

  async function exportFeed() {
    setExporting(true);
    setError(null);

    try {
      const allItems = await fetchAllActivityFeed(days, showPageVisits ? 'navigation' : undefined);
      downloadActivityCsv(allItems, activityFeedExportFilename(days));
    } catch (exportError: unknown) {
      setError(exportError instanceof Error ? exportError.message : 'Failed to export activity');
    } finally {
      setExporting(false);
    }
  }

  async function exportFeedServer() {
    setExportingServer(true);
    setError(null);

    try {
      await downloadActivityExport(
        days,
        activityFeedExportFilename(days),
        showPageVisits ? 'navigation' : undefined,
      );
    } catch (exportError: unknown) {
      setError(exportError instanceof Error ? exportError.message : 'Failed to export activity');
    } finally {
      setExportingServer(false);
    }
  }

  const emptyMessage = showPageVisits
    ? 'No page visits yet. Open a lead, store, or settings page and stay a few seconds — list-only grid pages are not logged.'
    : 'No staff actions recorded yet.';

  return (
    <>
      <PageHeader
        title="Activity history"
        description={
          showPageVisits
            ? 'Pages staff opened in the app (detail and settings routes).'
            : 'Logins, updates, messages, and other staff actions.'
        }
      />

      <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center">
        <div
          className="inline-flex flex-wrap gap-1 rounded-lg border border-border bg-surface p-1"
          role="group"
          aria-label="Activity feed type"
        >
          <button
            type="button"
            className={cn(
              feedMode === 'actions' ? segmentActive : segmentInactive,
              focusRing,
            )}
            aria-pressed={feedMode === 'actions'}
            onClick={() => setFeedMode('actions')}
          >
            Staff actions
          </button>
          <button
            type="button"
            className={cn(
              feedMode === 'navigation' ? segmentActive : segmentInactive,
              focusRing,
            )}
            aria-pressed={feedMode === 'navigation'}
            onClick={() => setFeedMode('navigation')}
          >
            Page visits
          </button>
        </div>

        <div className="flex flex-wrap items-center gap-3">
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
      </div>

      {error ? <Alert variant="error" className="mb-4">{error}</Alert> : null}

      {showPageVisits && navigationTableReady === false ? (
        <Alert variant="info" className="mb-4">
          Page-visit tracking is not set up on this server. Run{' '}
          <code className="text-sm">php artisan migrate --force</code> on Postgres, then restart{' '}
          <code className="text-sm">php artisan serve</code> (a stale process may still be using
          SQLite from <code className="text-sm">DB_CONNECTION=sqlite</code> in your shell).
        </Alert>
      ) : null}

      {loading ? <LoadingState label="Loading activity…" /> : null}

      {!loading ? (
        <Card>
          <CardHeader
            title={showPageVisits ? 'Page visits' : 'Staff actions'}
            actions={
              <div className="flex flex-wrap items-center gap-2">
                <ExportCsvButton
                  exporting={exporting}
                  disabled={items.length === 0}
                  onClick={exportFeed}
                />
                <ExportCsvButton
                  exporting={exportingServer}
                  disabled={items.length === 0}
                  onClick={exportFeedServer}
                  label="Export (server)"
                  busyLabel="Exporting…"
                />
              </div>
            }
          />
          <ActivityFeedList items={items} emptyMessage={emptyMessage} />
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
