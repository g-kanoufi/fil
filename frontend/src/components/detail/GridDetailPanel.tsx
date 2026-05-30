import { SlideOver } from '@/components/ui/SlideOver';
import { ContactDetailPage } from '@/pages/ContactDetailPage';
import { LeadDetailPage } from '@/pages/LeadDetailPage';
import { StoreDetailPage } from '@/pages/StoreDetailPage';

interface GridDetailPanelProps {
  resource: 'leads' | 'stores' | 'contacts';
  recordId: number | null;
  open: boolean;
  onClose: () => void;
}

export function GridDetailPanel({ resource, recordId, open, onClose }: GridDetailPanelProps) {
  if (!open || recordId === null) {
    return null;
  }

  const fullPagePath = `/reports/${resource}/${recordId}`;

  return (
    <SlideOver
      open={open}
      onClose={onClose}
      ariaLabel={`${resource.slice(0, -1)} detail`}
    >
      {resource === 'leads' ? (
        <LeadDetailPage recordId={recordId} layout="panel" onPanelClose={onClose} fullPagePath={fullPagePath} />
      ) : null}
      {resource === 'stores' ? (
        <StoreDetailPage recordId={recordId} layout="panel" onPanelClose={onClose} fullPagePath={fullPagePath} />
      ) : null}
      {resource === 'contacts' ? (
        <ContactDetailPage recordId={recordId} layout="panel" onPanelClose={onClose} fullPagePath={fullPagePath} />
      ) : null}
    </SlideOver>
  );
}
