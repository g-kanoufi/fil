import { TextLink } from '@/components/ui/TextLink';
import { Badge } from '@/components/ui/Badge';
import type { ActivityItem } from '@/lib/api/activity';

interface ActivityFeedListProps {
  items: ActivityItem[];
  emptyMessage?: string;
  hideEmptyMessage?: boolean;
}

export function ActivityFeedList({
  items,
  emptyMessage = 'No activity recorded yet.',
  hideEmptyMessage = false,
}: ActivityFeedListProps) {
  if (items.length === 0) {
    if (hideEmptyMessage) {
      return null;
    }

    return <p className="text-sm text-muted">{emptyMessage}</p>;
  }

  return (
    <ul className="space-y-3">
      {items.map((item) => (
        <li key={item.id} className="rounded-lg border border-border p-4">
          <div className="flex flex-wrap items-center gap-2 text-xs text-muted">
            <Badge variant="info">{item.category}</Badge>
            <span>{item.action}</span>
            <span>{item.occurred_at ? new Date(item.occurred_at).toLocaleString() : '—'}</span>
          </div>
          <p className="mt-2 text-sm text-foreground">{item.summary}</p>
          {item.subject?.path ? (
            <TextLink to={item.subject.path} className="mt-2 inline-block text-sm">
              {item.subject.label} →
            </TextLink>
          ) : null}
        </li>
      ))}
    </ul>
  );
}
