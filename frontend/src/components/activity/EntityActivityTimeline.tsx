import { useEffect, useState } from 'react';
import { TextLink } from '@/components/ui/TextLink';
import { ActivityFeedList } from '@/components/activity/ActivityFeedList';
import { Card, CardHeader } from '@/components/ui/Card';
import { LoadingState } from '@/components/ui/LoadingState';
import { fetchSubjectActivity, type ActivityItem } from '@/lib/api/activity';

interface EntityActivityTimelineProps {
  subjectType: 'lead' | 'store' | 'contact' | 'user';
  subjectId: number;
}

export function EntityActivityTimeline({ subjectType, subjectId }: EntityActivityTimelineProps) {
  const [items, setItems] = useState<ActivityItem[]>([]);
  const [loading, setLoading] = useState(true);

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

  if (loading) {
    return <LoadingState label="Loading activity…" />;
  }

  return (
    <Card>
      <CardHeader
        title="Activity"
        description="Recent changes for this record."
        actions={
          <TextLink to="/history" className="text-sm">
            All activity →
          </TextLink>
        }
      />
      <ActivityFeedList items={items} emptyMessage="No activity logged for this record yet." />
    </Card>
  );
}
