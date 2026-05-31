import { Button } from '@/components/ui/Button';

interface ExportCsvButtonProps {
  onClick: () => void | Promise<void>;
  exporting?: boolean;
  disabled?: boolean;
  size?: 'sm' | 'md';
  className?: string;
  label?: string;
  busyLabel?: string;
}

export function ExportCsvButton({
  onClick,
  exporting = false,
  disabled = false,
  size = 'sm',
  className,
  label = 'Export CSV',
  busyLabel = 'Exporting…',
}: ExportCsvButtonProps) {
  return (
    <Button
      type="button"
      variant="secondary"
      size={size}
      className={className}
      disabled={disabled || exporting}
      onClick={() => void onClick()}
    >
      {exporting ? busyLabel : label}
    </Button>
  );
}
