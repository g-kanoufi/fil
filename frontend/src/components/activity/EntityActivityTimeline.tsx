import { useEffect, useState } from 'react';
import { ExportCsvButton } from '@/components/export/ExportCsvButton';
import { TextLink } from '@/components/ui/TextLink';
import { ActivityFeedList } from '@/components/activity/ActivityFeedList';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
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
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    let cancelled = false;

    void fetchSubjectActivity(subjectType, subjectId, 15)
      .then((response) => {
        if (!cancelled) {
          setItems(response.data);
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false);
        }
      });

    return () => {
      cancelled = true;
    };
  }, [subjectId, subjectType]);

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

  if (loading) {
    return <LoadingState label="Loading activity…" />;
  }

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
      <ActivityFeedList items={items} emptyMessage="No activity logged for this record yet." />
    </Card>
  );
}
