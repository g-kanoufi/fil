import { Button } from '@/components/ui/Button';

interface ExportCsvButtonProps {
  onClick: () => void | Promise<void>;
  exporting?: boolean;
  disabled?: boolean;
  size?: 'sm' | 'md';
  className?: string;
}

export function ExportCsvButton({
  onClick,
  exporting = false,
  disabled = false,
  size = 'sm',
  className,
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
      {exporting ? 'Exporting…' : 'Export CSV'}
    </Button>
  );
}
