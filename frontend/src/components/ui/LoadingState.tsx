interface LoadingStateProps {
  label?: string;
  className?: string;
}

export function LoadingState({ label = 'Loading…', className }: LoadingStateProps) {
  return (
    <div
      data-testid="loading-state"
      className={`flex min-h-[12rem] items-center justify-center text-sm text-muted${className ? ` ${className}` : ''}`}
    >
      <span className="inline-flex items-center gap-2">
        <span className="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
        {label}
      </span>
    </div>
  );
}
