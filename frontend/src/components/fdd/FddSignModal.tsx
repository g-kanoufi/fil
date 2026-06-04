import { useId } from 'react';
import { FddSignForm } from '@/components/fdd/FddSignForm';
import { ModalDialog } from '@/components/ui/ModalDialog';

interface FddSignModalProps {
  deliveryLabel: string;
  open: boolean;
  onClose: () => void;
  onSubmit: (payload: { signed_name: string; agree: boolean }) => Promise<void>;
}

export function FddSignModal({ deliveryLabel, open, onClose, onSubmit }: FddSignModalProps) {
  const titleId = useId();
  const descriptionId = useId();

  return (
    <ModalDialog
      open={open}
      onClose={onClose}
      labelledBy={titleId}
      describedBy={descriptionId}
      className="max-w-md"
    >
      <h2 id={titleId} className="text-lg font-semibold text-foreground">
        Record FDD signature
      </h2>
      <p id={descriptionId} className="mt-1 text-sm text-muted">
        {deliveryLabel}
      </p>

      <div className="mt-5">
        <FddSignForm
          onSubmit={async (payload) => {
            await onSubmit(payload);
            onClose();
          }}
          onCancel={onClose}
        />
      </div>
    </ModalDialog>
  );
}
