import { cn } from '@/lib/cn';

interface AvatarProps {
  name: string;
  className?: string;
  size?: 'sm' | 'md';
}

function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);

  if (parts.length === 0) {
    return '?';
  }

  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }

  return `${parts[0][0] ?? ''}${parts[parts.length - 1][0] ?? ''}`.toUpperCase();
}

export function Avatar({ name, className, size = 'md' }: AvatarProps) {
  const sizeClass = size === 'sm' ? 'h-8 w-8 text-xs' : 'h-10 w-10 text-sm';

  return (
    <span
      aria-hidden
      className={cn(
        'inline-flex items-center justify-center rounded-full bg-primary font-semibold text-primary-fg shadow-sm ring-2 ring-white/20',
        sizeClass,
        className,
      )}
    >
      {initials(name)}
    </span>
  );
}
