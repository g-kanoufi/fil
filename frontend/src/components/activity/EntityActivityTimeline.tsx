import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { TextLink } from '@/components/ui/TextLink';
import { ActivityFeedList } from '@/components/activity/ActivityFeedList';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { fetchSubjectActivity, type ActivityItem } from '@/lib/api/activity';
import { downloadActivityCsv } from '@/lib/export/activityCsv';

interface EntityActivityTimelineProps {
  subjectType: 'lead' | 'store' | 'contact' | 'user';
  subjectId: number;
}

export function EntityActivityTimeline({ subjectType, subjectId }: EntityActivityTimelineProps) {
  const [items, setItems] = useState<ActivityItem[]>([]);
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
      downloadActivityCsv(response.data, `${subjectType}-${subjectId}-activity.csv`);
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
          <div className="flex items-center gap-3">
            <Button
              variant="secondary"
              size="sm"
              disabled={exporting}
              onClick={() => void exportTimeline()}
            >
              {exporting ? 'Exporting…' : 'Export CSV'}
            </Button>
            <TextLink to="/history" className="text-sm">
              All activity →
            </TextLink>
          </div>
        }
      />
      <ActivityFeedList items={items} emptyMessage="No activity logged for this record yet." />
    </Card>
  );
}
