import { useCallback, useEffect, useState } from 'react';
import { ExportCsvButton } from '@/components/export/ExportCsvButton';
import { TextLink } from '@/components/ui/TextLink';
import { ActivityFeedList } from '@/components/activity/ActivityFeedList';
import { AsyncSection } from '@/components/ui/AsyncSection';
import { Card, CardHeader } from '@/components/ui/Card';
import { fetchSubjectActivity } from '@/lib/api/activity';
import { downloadActivityCsv } from '@/lib/export/activityCsv';
import { subjectActivityExportFilename } from '@/lib/export/filenames';

interface EntityActivityTimelineProps {
  subjectType: 'lead' | 'store' | 'contact' | 'user';
  subjectId: number;
}

export function EntityActivityTimeline({ subjectType, subjectId }: EntityActivityTimelineProps) {
  const [items, setItems] = useState<Awaited<ReturnType<typeof fetchSubjectActivity>>['data']>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetchSubjectActivity(subjectType, subjectId, 15);
      setItems(response.data);
    } catch (loadError: unknown) {
      setError(loadError instanceof Error ? loadError.message : 'Failed to load activity');
      setItems([]);
    } finally {
      setLoading(false);
    }
  }, [subjectId, subjectType]);

  useEffect(() => {
    void load();
  }, [load]);

  async function exportTimeline() {
    setExporting(true);
    try {
      const response = await fetchSubjectActivity(subjectType, subjectId, 200);
      downloadActivityCsv(
        response.data,
        subjectActivityExportFilename(subjectType, subjectId),
      );
    } finally {
      setExporting(false);
    }
  }

  const status = loading ? 'loading' : error ? 'error' : items.length === 0 ? 'empty' : 'ready';

  return (
    <Card>
      <CardHeader
        title="Activity"
        description="Recent changes for this record."
        actions={
          <>
            <ExportCsvButton
              exporting={exporting}
              disabled={items.length === 0}
              onClick={exportTimeline}
            />
            <TextLink to="/history" className="self-center text-sm">
              All activity →
            </TextLink>
          </>
        }
      />
      <AsyncSection
        status={status}
        loadingLabel="Loading activity…"
        error={error}
        onRetry={() => void load()}
        emptyTitle="No activity logged yet"
        emptyDescription="Changes to this record will appear here."
      >
        <ActivityFeedList items={items} hideEmptyMessage />
      </AsyncSection>
    </Card>
  );
}
