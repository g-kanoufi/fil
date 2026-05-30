import type { InputHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/cn';
import { formControl, formControlError, formFocus } from '@/lib/ui/tokens';

interface FormFieldProps extends InputHTMLAttributes<HTMLInputElement> {
  label: string;
  hint?: ReactNode;
  error?: string | null;
}

export function FormField({ label, hint, error, id, className, ...props }: FormFieldProps) {
  const fieldId = id ?? props.name;

  return (
    <div className="space-y-1.5">
      <label htmlFor={fieldId} className="block text-sm font-medium text-foreground">
        {label}
      </label>
      <input
        id={fieldId}
        className={cn(formControl, formFocus, error ? formControlError : '', className)}
        {...props}
      />
      {hint ? <p className="text-xs text-muted">{hint}</p> : null}
      {error ? <p role="alert" className="text-xs text-error">{error}</p> : null}
    </div>
  );
}
